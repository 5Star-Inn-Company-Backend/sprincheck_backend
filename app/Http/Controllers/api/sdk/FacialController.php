<?php

namespace App\Http\Controllers\api\sdk;

use App\Http\Controllers\Controller;
use App\Jobs\ServiceDebitJob;
use App\Jobs\WebhookNotificationJob;
use App\Models\KycFace;
use App\Models\KycLog;
use App\Models\KycNIN;
use App\Services\MonoService;
use App\Services\PremblyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FacialController extends Controller
{
    public function check(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'identifier' => 'required'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $biz=$request->get('biz');

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"FACIAL");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $kyc=KycLog::where([['identifier', $input['identifier']], ['business_id', $biz->id]])->first();

        $ref=$biz->id."_".rand();

        if($kyc){
            KycFace::create([
                "user_id" => $biz->id,
                "reference" => $ref,
                "link" => $kyc->id,
                "source_image" => $kyc->image,
                "source" => "INTERNAL"
            ]);
            return response()->json(['success' => 1, 'message' => 'Fetched Successfully', 'confidence_level'=>$biz->confidence_level, 'data' => ['image' => $kyc->image, 'reference' =>$ref]]);
        }else{
            return response()->json(['success' => 0, 'message' => "Identifier not found"]);
        }

    }

    public function sdk_resp(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'reference' => 'required',
            'image' => 'required',
            'confidence' => 'required',
            'identifier' => 'required'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $biz=$request->get('biz')->refresh();

        $kyc=KycLog::where([['identifier', $input['reference']], ['business_id', $biz->id]])->first();

        if($kyc){
            return response()->json(['success' => 0, 'message' => 'Kindly start the process afresh']);
        }

        $kyc=KycLog::where([['identifier', $input['identifier']], ['business_id', $biz->id]])->first();

        if(!$kyc){
            return response()->json(['success' => 0, 'message' => 'Kindly provide valid identifier']);
        }

        Log::info("Running FACIAL Valid on");


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
                'type' => 'FACIAL VERIFICATION',
                'source' => 'API',
                'status' =>doubleval($input['confidence']) >= doubleval($biz->confidence_level) ?'1':'0',
                'confidence' => $input['confidence'],
                'identifier' => $input['reference'],
                'reference' => $input['reference'],
                'image' => $url
            ]);

            if($k->status == 1){
                $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"FACIAL");
                ServiceDebitJob::dispatch($fee, $input['reference'],$biz, 'FACIAL_VERIFICATION');
            }

            if($biz->webhook_url) {
                WebhookNotificationJob::dispatch($k, 'FACIAL_VERIFICATION');
            }

            return response()->json(['success' => 1, 'message' => 'Recorded Successfully', 'data' => $k->status == 1 ? "SUCCESS":"FAILED"]);

        } catch (\Exception $e) {
            Log::info("Error encountered when updating  on " . $e);
            Log::info($e);

            return response()->json(['success' => 0, 'message' => 'Unable to verify try again later.']);
        }

    }

}
