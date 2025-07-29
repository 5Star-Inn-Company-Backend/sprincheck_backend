<?php

namespace App\Http\Controllers\api\sdk;

use App\Http\Controllers\Controller;
use App\Jobs\ServiceDebitJob;
use App\Jobs\WebhookNotificationJob;
use App\Models\Kyc;
use App\Models\KycDriversLicense;
use App\Models\KycLog;
use App\Models\KycVoters;
use App\Services\MonoService;
use App\Services\PremblyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VotersController extends Controller
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
            'number' => 'required',
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

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"VOTERS");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $kyc=KycVoters::where('number', $input['number'])->first();

        if($kyc){
            ServiceDebitJob::dispatch($fee, $reference,$biz,'VOTERS_VERIFICATION');

            $data=json_decode($kyc->data,true);
        }else{

            Log::info("Running Kyc check on ".$input['number']);

            try {
                $userService = new PremblyService();
                    $res=$userService->voters($input['number'],$biz->id,"API");
                $kyc=$res['kyc'];
                $data=$res['data'];
            } catch (\Exception $e) {
                Log::error("Error on VOTERS_VERIFICATION", [$e]);
                return response()->json(['success' => 0, 'message' => $e->getMessage()]);
            }
        }

        $k=KycLog::create([
            'kyc_id' => $kyc->id,
            'business_id' => $biz->id,
            'user_id' => $request->get('user')->id,
            'billing_id' => 1,
            'type' => 'VOTERS VERIFICATION',
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

        ServiceDebitJob::dispatch($fee, $reference,$biz, 'VOTERS_VERIFICATION');

        return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'data' => $data]);
    }

}

//PREMBLY
//{
//    "status": true,
//    "detail": "Verification Successful",
//    "response_code": "00",
//    "data": {
//    "fullName": " Blessing Aanuoluwapo Afolabi ",
//        "gender": "F",
//        "occupation": "STUDENT",
//        "photo": "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",
//        "state": "OYO",
//        "lga": "IBADAN NORTH EAST",
//        "address": "E7/1207 YIDI OREMEJI, IBADAN, IBADAN NORTH EAST, OYO",
//        "vin": "90F5AE4625505997419",
//        "country": "NG",
//        "date_of_birth": "",
//        "pollingUnit": "",
//        "registrationAreaWard": "",
//        "timeOfRegistration": "",
//        "pollingUnitCode": "",
//        "phone_number": ""
//    },
//    "verification": {
//    "status": "VERIFIED",
//        "reference": "1fedaa1f-84c8-4ddf-a627-cb70290a81ab"
//    },
//    "widget_info": {},
//    "session": {},
//    "endpoint_name": "Voters card"
//}
