<?php

namespace App\Http\Controllers\api\sdk;

use App\Http\Controllers\Controller;
use App\Models\Kyc;
use App\Models\KycLog;
use App\Models\KycNIN;
use App\Services\MonoService;
use App\Services\PremblyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $kyc=KycNIN::where('nin', $input['number'])->first();

        if($kyc){
            $resp=json_decode($kyc->data,true);
            return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'confidence_level'=>$request->get('biz')->confidence_level, 'data' => ['image' => $resp['photo'], 'reference' =>$kyc->reference]]);
        }


        Log::info("Running Kyc check on ".$input['number']);

        try {

            if(env('NIN_VERIFICATION') == "MONO"){
                $userService = new MonoService();
                $data=$userService->nin($input['number'],$request->get('biz')->id);
            }else{
                $userService = new PremblyService();
                $data=$userService->nin($input['number'],$request->get('biz')->id);
            }

            return response()->json(['success' => 1, 'message' => 'Verified Successfully', 'confidence_level'=>$request->get('biz')->confidence_level, 'data' => $data]);

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
            'confidence' => 'required'
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

        try {

//            $file = $request->file('file');
            $fileName = Str::uuid() . '.jpg'; // Generate unique filename

            // Configure MinIO disk (ensure you have it configured in config/filesystems.php)
            $disk = Storage::disk('s3');

            // Upload the file to MinIO
            $disk->put($fileName, base64_decode($input['image']));

            // Get the URL of the uploaded file
            $url = $disk->url($fileName);

            KycLog::create([
                'kycnin_id' => $kyc->id,
                'business_id' => $request->get('biz')->id,
                'user_id' => $request->get('user')->id,
                'billing_id' => 1,
                'type' => 'NIN VERIFICATION',
                'source' => 'API',
                'status' =>doubleval($input['confidence']) >= doubleval($request->get('biz')->confidence_level) ?'1':'0',
                'confidence' => $input['confidence'],
                'image' => $url
            ]);

            return response()->json(['success' => 1, 'message' => 'Recorded Successfully', 'data' => $kyc->name]);

        } catch (\Exception $e) {
            Log::info("Error encountered when updating  on " . $e);
            Log::info($e);

            return response()->json(['success' => 0, 'message' => 'Unable to verify try again later.']);
        }

    }

}
