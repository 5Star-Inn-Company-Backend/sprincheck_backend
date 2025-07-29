<?php

namespace App\Http\Controllers\api\sdk;

use App\Http\Controllers\Controller;
use App\Jobs\ServiceDebitJob;
use App\Jobs\WebhookNotificationJob;
use App\Models\Kyc;
use App\Models\KycDriversLicense;
use App\Models\KycLog;
use App\Models\KycPassport;
use App\Services\MonoService;
use App\Services\PremblyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DriverLicenseController extends Controller
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
            'dob' => 'required',
            'last_name' => 'required',
            'first_name' => 'required',
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

        $fee= (new \App\Models\TransactionFee)->getTransactionFee($biz->id,"DRIVERLICENSE");

        if($fee > $biz->wallet){
            return response()->json(['success' => 0, 'message' => "It cannot be processed. Check your wallet balance"]);
        }

        $kyc=KycDriversLicense::where('number', $input['number'])->first();

        if($kyc){
            ServiceDebitJob::dispatch($fee, $reference,$biz,'DRIVERLICENSE_VERIFICATION');

            $data=json_decode($kyc->data,true);
        }else{

            Log::info("Running Kyc check on ".$input['number']);

            try {
                $userService = new MonoService();
                    $res=$userService->drivers_license($input['number'],$input['dob'],$input['first_name'],$input['last_name'],$biz->id,"API");
                $kyc=$res['kyc'];
                $data=$res['data'];
            } catch (\Exception $e) {
                Log::error("Error on DRIVERLICENSE_VERIFICATION", [$e]);
                return response()->json(['success' => 0, 'message' => $e->getMessage()]);
            }
        }

        $k=KycLog::create([
            'kyc_id' => $kyc->id,
            'business_id' => $biz->id,
            'user_id' => $request->get('user')->id,
            'billing_id' => 1,
            'type' => 'DRIVERLICENSE VERIFICATION',
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

        ServiceDebitJob::dispatch($fee, $reference,$biz, 'DRIVERLICENSE_VERIFICATION');

        return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'data' => $data]);
    }

}

//MOMO
//{
//    "status": "successful",
//    "message": "Lookup Successful",
//    "timestamp": "2025-07-29T00:31:02.940Z",
//    "data": {
//    "gender": "",
//        "photo": null,
//        "license_no": "FKJ06957AC15",
//        "first_name": "SAMUEL",
//        "last_name": "ODEJINMI",
//        "middle_name": "",
//        "issued_date": "",
//        "expiry_date": "2029-12-12",
//        "state_ofIssue": "",
//        "birth_date": "1996-12-12"
//    }
//}
