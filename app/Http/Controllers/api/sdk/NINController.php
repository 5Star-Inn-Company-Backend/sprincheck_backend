<?php

namespace App\Http\Controllers\api\sdk;

use App\Http\Controllers\Controller;
use App\Jobs\ServiceDebitJob;
use App\Jobs\WebhookNotificationJob;
use App\Models\KycLog;
use App\Models\KycNIN;
use App\Services\MonoService;
use App\Services\PremblyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NINController extends Controller
{
    public function check(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'number' => 'required|digits:11'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $biz=$request->get('biz')->refresh();


//        $kyc=KycLog::where([['identifier', $input['identifier']], ['business_id', $biz->id]])->first();
//
//        if($kyc){
//            return response()->json(['success' => 0, 'message' => 'Identifier already exist. Kindly try again with a unique identifier']);
//        }

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"NIN");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $kyc=KycNIN::where('nin', $input['number'])->first();

        if($kyc){
            ServiceDebitJob::dispatch($fee, $kyc->reference,$biz,'NIN_VERIFICATION');

            $resp=json_decode($kyc->data,true);
            return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'confidence_level'=>$biz->confidence_level, 'data' => ['image' => $resp['photo'], 'reference' =>$kyc->reference]]);
        }


        // Check if number starts with 0 or 1
        if (preg_match('/^[01]/', $input['number'])) {
            return response()->json(['success' => 0, 'message' => 'Invalid number: cannot start with 0 or 1.']);
        }

        Log::info("Running Kyc check on ".$input['number']);

        try {

            if(env('NIN_VERIFICATION') == "MONO"){
                $userService = new MonoService();
                $data=$userService->nin($input['number'],$biz->id);
            }else{
                $userService = new PremblyService();
                $data=$userService->nin($input['number'],$biz->id);
            }

            ServiceDebitJob::dispatch($fee, $data['reference'],$biz, 'NIN_VERIFICATION');

            return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'confidence_level'=>$biz->confidence_level, 'data' => $data]);

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

        $kyc=KycNIN::where([['nin', $input['number']],['reference', $input['reference']]])->first();

        if(!$kyc){
            return response()->json(['success' => 0, 'message' => 'Kindly provide valid Kyc']);
        }

        Log::info("Running NIN Valid on");


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
                'type' => 'NIN VERIFICATION',
                'source' => 'API',
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
            'number' => 'required|digits:11',
            'identifier' => 'required'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }

        $reference=Str::uuid();
        $biz=$request->get('biz')->refresh();


        $kyc=KycLog::where([['identifier', $input['identifier']], ['business_id', $biz->id]])->first();

        if($kyc){
            return response()->json(['success' => 0, 'message' => 'Identifier already exist. Kindly try again with a unique identifier']);
        }

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"NIN");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $kyc=KycNIN::where('nin', $input['number'])->first();

        if($kyc){
            ServiceDebitJob::dispatch($fee, $kyc->reference,$biz,'NIN_VERIFICATION');

            $data=json_decode($kyc->data,true);
        }else{

            // Check if number starts with 0 or 1
            if (preg_match('/^[0]/', $input['number'])) {
                return response()->json(['success' => 0, 'message' => 'Invalid number: cannot start with 0.']);
            }

            Log::info("Running Kyc check on ".$input['number']);

            try {
                if(env('NIN_VERIFICATION') == "MONO"){
                    $userService = new MonoService();
                    $res=$userService->nin($input['number'],$biz->id,"API");
                }else{
                    $userService = new PremblyService();
                    $res=$userService->nin($input['number'],$biz->id,"API");
                }
                $kyc=$res['kyc'];
                $data=$res['data'];

            } catch (\Exception $e) {
                Log::error("Error on NIN", [$e]);
                return response()->json(['success' => 0, 'message' => $e->getMessage()]);
            }
        }


        $k=KycLog::create([
            'kyc_id' => $kyc->id,
            'business_id' => $biz->id,
            'user_id' => $request->get('user')->id,
            'billing_id' => 1,
            'type' => 'NIN VERIFICATION',
            'source' => 'API',
            'status' =>"1",
            'confidence' => "0",
            'identifier' => $input['identifier'],
            'reference' => $reference,
            'image' => ""
        ]);

        if($biz->webhook_url) {
            WebhookNotificationJob::dispatch($k, $input['number']);
        }

        ServiceDebitJob::dispatch($fee, $reference,$biz, 'NIN_VERIFICATION');

        return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'data' => $data]);
    }

}
