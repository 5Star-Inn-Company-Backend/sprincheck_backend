<?php

namespace App\Http\Controllers\api\sdk;

use App\Http\Controllers\Controller;
use App\Jobs\ServiceDebitJob;
use App\Jobs\WebhookNotificationJob;
use App\Models\Kyc;
use App\Models\KycLog;
use App\Services\PremblyService;
use App\Services\StarFaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FaceRecognitionController extends Controller
{
    public function agengender(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'image' => 'required'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $biz=$request->get('biz')->refresh();

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"BVN");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $kyc=Kyc::where('bvn', $input['number'])->first();

        if($kyc){
            ServiceDebitJob::dispatch($fee, $kyc->reference,$biz,'BVN_VERIFICATION');

            $resp=json_decode($kyc->data,true);
            return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'confidence_level'=>$biz->confidence_level, 'data' => ['image' => $resp['base64Image'], 'reference' =>$kyc->reference]]);
        }


        // Check if number starts with 0 or 1
        if (preg_match('/^[01]/', $input['number'])) {
            return response()->json(['success' => 0, 'message' => 'Invalid number: cannot start with 0 or 1.']);
        }

        Log::info("Running Kyc check on ".$input['number']);

        try {
            $userService = new PremblyService();
            $data=$userService->bvn($input['number'],$biz->id);

            ServiceDebitJob::dispatch($fee, $data['reference'],$biz, 'BVN_VERIFICATION');

            return response()->json(['success' => 1, 'message' => 'Verified Successfully',  'confidence_level'=>$biz->confidence_level, 'data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['success' => 0, 'message' => $e->getMessage()]);
        }

    }

    public function sdk_resp(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'number' => 'required|digits:11',
            'reference' => 'required',
            'image' => 'required',
            'confidence' => 'required',
            'identifier' => 'required'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $kyc=Kyc::where([['bvn', $input['number']],['reference', $input['reference']]])->first();

        if(!$kyc){
            return response()->json(['success' => 0, 'message' => 'Kindly provide valid Kyc']);
        }

        Log::info("Running BVN Valid on");

        $biz=$request->get('biz')->refresh();

        try {

//            $file = $request->file('file');
            $fileName = Str::uuid() . '.jpg'; // Generate unique filename

            // Configure MinIO disk (ensure you have it configured in config/filesystems.php)
            $disk = Storage::disk('s3');

            // Upload the file to MinIO
            $disk->put($fileName, base64_decode($input['image']));

            // Get the URL of the uploaded file
            $url = $disk->url($fileName);

            $k=KycLog::create([
                'kyc_id' => $kyc->id,
                'business_id' => $biz->id,
                'user_id' => $request->get('user')->id,
                'billing_id' => 1,
                'type' => 'BVN VERIFICATION',
                'source' => 'SDK',
                'status' =>doubleval($input['confidence']) >= doubleval($biz->confidence_level) ?'1':'0',
                'confidence' => $input['confidence'],
                'identifier' => $input['identifier'],
                'reference' => $input['reference'],
                'image' => $url
            ]);

            if($biz->webhook_url) {
                WebhookNotificationJob::dispatch($k, $input['number']);
            }

            return response()->json(['success' => 1, 'message' => 'Recorded Successfully', 'data' => $kyc->name]);

        } catch (\Exception $e) {
            Log::info("Error encountered when updating  on " . $e);
            Log::info($e);

            return response()->json(['success' => 0, 'message' => 'Unable to verify try again later.']);
        }

    }

    public function merchant(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'image' => 'required'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $reference=Str::uuid();
        $biz=$request->get('biz')->refresh();

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"BVN");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $fileName = "faces/". $reference.".jpg";

        $disk = Storage::disk('s3');

        $disk->put($fileName, base64_decode($input['image']));

        $storage = $disk->url($fileName);

        Log::info("Running face detection check on ".$biz->name." for ".$reference. " with ". $storage);

        try {
            $userService = new StarFaceService();
            $res=$userService->detectFace($storage,$biz->id,"API");
            $kyc=$res['kyc'];
            $data=$res['data'];

        } catch (\Exception $e) {
            Log::error("Error on Face Detection", [$e]);
            return response()->json(['success' => 0, 'message' => $e->getMessage()]);
        }


        $k=KycLog::create([
            'kyc_id' => $kyc->id,
            'business_id' => $biz->id,
            'user_id' => $request->get('user')->id,
            'billing_id' => 1,
            'type' => 'FACE DETECTION',
            'source' => 'API',
            'status' =>"1",
            'confidence' => "0",
            'identifier' => $reference,
            'reference' => $reference,
            'image' => $storage
        ]);

        if($biz->webhook_url) {
            WebhookNotificationJob::dispatch($k, $reference);
        }

        ServiceDebitJob::dispatch($fee, $reference,$biz, 'FACE_DETECTION');

        if($res['detected']){
            return response()->json(['success' => 1, 'message' => 'Face Detected Successfully', 'data' => $data]);
        }else{
            return response()->json(['success' => 0, 'message' => 'Face not detected', 'data' => $data]);
        }

    }

    public function merchant_liveness(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'image' => 'required'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $reference=Str::uuid();
        $biz=$request->get('biz')->refresh();

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"BVN");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $fileName = "faces/". $reference.".jpg";

        $disk = Storage::disk('s3');

        $disk->put($fileName, base64_decode($input['image']));

        $storage = $disk->url($fileName);

        Log::info("Running face detection check on ".$biz->name." for ".$reference. " with ". $storage);

        try {
            $userService = new StarFaceService();
            $res=$userService->detectFaceLiveness($storage,$biz->id,"API");
            $kyc=$res['kyc'];
            $data=$res['data'];

        } catch (\Exception $e) {
            Log::error("Error on Face Detection", [$e]);
            return response()->json(['success' => 0, 'message' => $e->getMessage()]);
        }


        $k=KycLog::create([
            'kyc_id' => $kyc->id,
            'business_id' => $biz->id,
            'user_id' => $request->get('user')->id,
            'billing_id' => 1,
            'type' => 'FACE LIVENESS',
            'source' => 'API',
            'status' =>"1",
            'confidence' => "0",
            'identifier' => $reference,
            'reference' => $reference,
            'image' => $storage
        ]);

        if($biz->webhook_url) {
            WebhookNotificationJob::dispatch($k, $reference);
        }

        ServiceDebitJob::dispatch($fee, $reference,$biz, 'FACE_LIVENESS');

        if($res['liveness']){
            return response()->json(['success' => 1, 'message' => 'Face Liveness Detected Successfully', 'data' => $data]);
        }else{
            return response()->json(['success' => 0, 'message' => 'Liveness not detected in the given image.']);
        }

    }

    public function merchant_compare(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'image1' => 'required|string|max:685000',
            'image2' => 'required|string|max:685000'
        );

        $validator = Validator::make($input, $rules,[
            'image1.required' => 'The image1 field is required.',
            'image1.max' => 'The image1 field must not exceed 500 kilobytes.',
            'image2.required' => 'The image2 field is required.',
            'image2.max' => 'The image2 field must not exceed 500 kilobytes.',
        ]);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $reference=Str::uuid();
        $biz=$request->get('biz')->refresh();

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"BVN");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $fileName1 = "faces/". $reference."_1.jpg";
        $fileName2 = "faces/". $reference."_2.jpg";

        $disk = Storage::disk('s3');

        $disk->put($fileName1, base64_decode($input['image1']));

        $storage1 = $disk->url($fileName1);

        $disk->put($fileName2, base64_decode($input['image2']));

        $storage2 = $disk->url($fileName2);

        Log::info("Running face detection check on ".$biz->name." for ".$reference. " with ". $storage1." and ". $storage2);

        try {
            $userService = new StarFaceService();
            $res=$userService->compareFace($storage1,$storage2,$biz->id,"API");
            $kyc=$res['kyc'];
            $data=$res['data'];

        } catch (\Exception $e) {
            Log::error("Error on Face Detection", [$e]);
            return response()->json(['success' => 0, 'message' => $e->getMessage()]);
        }

        $k=KycLog::create([
            'kyc_id' => $kyc->id,
            'business_id' => $biz->id,
            'user_id' => $request->get('user')->id,
            'billing_id' => 1,
            'type' => 'FACE COMPARE',
            'source' => 'API',
            'status' =>"1",
            'confidence' => "0",
            'identifier' => $reference,
            'reference' => $reference,
            'image' => $storage1
        ]);

        if($biz->webhook_url) {
            WebhookNotificationJob::dispatch($k, $reference);
        }

        ServiceDebitJob::dispatch($fee, $reference,$biz, 'FACE_COMPARE');

        if($res['verified']){
            return response()->json(['success' => 1, 'message' => 'Matched Successfully', 'data' => $data]);
        }else{
            return response()->json(['success' => 0, 'message' => 'Face not matched', 'data' => $data]);
        }

    }

}
