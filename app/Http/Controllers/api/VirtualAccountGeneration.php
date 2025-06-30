<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\VirtualAccount;
use App\Models\VirtualAccountClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VirtualAccountGeneration extends Controller
{
    function generate(Request $request)
    {

        $bizz = Business::find(Auth::user()->company_id);

        $u = Auth::user();

        $gender = "Male";
        $address = $bizz->address;
        $dob = "1990-10-10";
        $phone = preg_replace('/234/', '0', $bizz->phone, 1);
        $email = $bizz->email;

        if ($email == "") {
            return redirect('/business')->with('error', "Kindly update your Business Email and try again");
        }

        if ($phone == "") {
            return redirect('/business')->with('error', "Kindly update your Business Phone Number and try again");
        }

//        if ($address == "") {
//            return redirect('/business')->with('error', "Kindly update your Business Address and try again");
//        }

        if (is_null($bizz->name)) {
            return redirect('/business')->with('error', "Kindly update your Business Name and try again");
        }

        if (is_null($bizz->bvn)) {
            return redirect('/business')->with('error', "Kindly update your BVN and try again");
        }

        if (strlen($bizz->bvn) != 11) {
            return redirect('/business')->with('error', "Invalid BVN provided. Kindly update your BVN and try again");
        }

        $emai = explode("@", $email);
        $email = "$emai[0]+" . rand(0, 5) . "@$emai[1]";
        $name=$u->last_name . " " . $u->first_name;

        $payload = '{
    "account_name": "' . $bizz->name . '",
    "uniqueid": "'.$emai[0].'",
    "business_short_name": "5RC",
    "name": "'.$name.'",
    "email": "' . $email . '",
    "bvn":"' . $bizz->bvn . '",
    "phone":"' . $bizz->phone . '",
    "webhook_url":"https://internal_neglect_url"
}';

        Log::info("Create Palmpay Account for " . $email);
        Log::info($payload);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('MCD_BASEURL') . 'v1/virtual-account4',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . env('MCD_TOKEN'),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

//        echo($response);

        Log::info("MCD Response for " . $email);
        Log::info($response);

        $rep = json_decode($response, true);

        try {
            if ($rep['respCode'] == "00000000" && $rep['status']) {

                VirtualAccountClient::create([
                    "business_id" => $bizz->id,
                    "account_reference" => $rep['data']['accountReference'],
                    "currency_code" => "NGN",
                    "customer_email" => $email,
                    "customer_phone" => $phone,
                    "customer_name" => $rep['data']['virtualAccountName'],
                    "account_number" => $rep['data']['virtualAccountNo'],
                    "bank_name" => "PalmPay",
                    "status" => "active",
                    "created_on" => Carbon::now(),
                    "reservation_reference" => $emai[0],
                    "webhook_url" => '',
                    "extra" => $response
                ]);

                $this->captureAudit("Business Account Generated Successfully - MCD-PALMPAY", 'business account', 'create');
                return redirect('/business')->with('success', 'Business Account generated successfully!');
            } else {
                $this->captureAudit("Business Account Generated Failed", 'business account', 'create');
                return redirect('/business')->with('error', "Error generating virtual account for your business. Retry later");
            }
        } catch (\Exception $e) {
            Log::error($e);
            $this->captureAudit("Business Account Generated Failed from provider", 'business account', 'create');
            return redirect('/business')->with('error', "Error generating virtual account for your business. Retry later");
        }
    }

    function generateKorapay(Request $request)
    {

        $bizz = Business::find(Auth::user()->company_id);

        $u = Auth::user();

        $gender = "Male";
        $address = $bizz->address;
        $dob = "1990-10-10";
        $phone = preg_replace('/234/', '0', $bizz->phone, 1);
        $email = $bizz->email;

        if ($email == "") {
            return redirect('/business')->with('error', "Kindly update your Business Email and try again");
        }

        if ($phone == "") {
            return redirect('/business')->with('error', "Kindly update your Business Phone Number and try again");
        }

//        if ($address == "") {
//            return redirect('/business')->with('error', "Kindly update your Business Address and try again");
//        }

        if (is_null($bizz->name)) {
            return redirect('/business')->with('error', "Kindly update your Business Name and try again");
        }

        if (is_null($bizz->bvn)) {
            return redirect('/business')->with('error', "Kindly update your BVN and try again");
        }

        if (strlen($bizz->bvn) != 11) {
            return redirect('/business')->with('error', "Invalid BVN provided. Kindly update your BVN and try again");
        }

        $emai = explode("@", $email);
        $email = "$emai[0]+" . rand(0, 5) . "@$emai[1]";
        $name=$u->last_name . " " . $u->first_name;

        $payload = '{
    "account_name": "' . $bizz->name . '",
    "account_reference": "'.$emai[0].'",
    "permanent": true,
    "bank_code": "'.env('KORAPAY_BANK').'",
    "customer": {
        "name": "'.$name.'",
        "email": "' . $email . '"
    },
    "kyc":{
        "bvn":"' . $bizz->bvn . '"
    }
}';

        Log::info("Create Korapay Account for " . $email);
        Log::info($payload);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('KORAPAY_BASEURL') . 'v1/virtual-bank-account',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . env('KORAPAY_SECRET_KEY'),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        echo($response);

        Log::info("Korapay Response for " . $email);
        Log::info($response);

        $rep = json_decode($response, true);

        try {
            if ($rep['status']) {

//                if ($rep['status'] == "08") {
//                    $this->captureAudit("Business Account Generated Failed. Kindly change your email address and try again", 'business account', 'create');
//                    return redirect('/business')->with('error', "Error generating virtual account for your business. Kindly change your email address and try again");
//                }

                VirtualAccountClient::create([
                    "business_id" => $bizz->id,
                    "account_reference" => $rep['data']['account_reference'],
                    "currency_code" => $rep['data']['currency'],
                    "customer_email" => $email,
                    "customer_phone" => $phone,
                    "customer_name" => $rep['data']['account_name'],
                    "account_number" => $rep['data']['account_number'],
                    "bank_name" => $rep['data']['bank_name'],
                    "status" => $rep['data']['account_status'],
                    "created_on" => Carbon::now(),
                    "reservation_reference" => $rep['data']['unique_id'],
                    "webhook_url" => '',
                    "extra" => $response
                ]);

                $this->captureAudit("Business Account Generated Successfully - " . $rep['data']['bank_name'], 'business account', 'create');
                return redirect('/business')->with('success', 'Business Account generated successfully!');
            } else {
                $this->captureAudit("Business Account Generated Failed", 'business account', 'create');
                return redirect('/business')->with('error', "Error generating virtual account for your business. Retry later");
            }
        } catch (\Exception $e) {
            $this->captureAudit("Business Account Generated Failed from provider", 'business account', 'create');
            return redirect('/business')->with('error', "Error generating virtual account for your business. Retry later");
        }
    }

    function generatePaylony(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'bvn' => 'required|digits:11'
        );

        $validator = Validator::make($input, $rules);

        if (!$validator->passes()) {
            return response()->json(['success' => 0, 'message' => implode(",", $validator->errors()->all())]);
        }


        $bizz = Business::find(Auth::user()->business_id);

        $u = Auth::user();


        // Get virtual accounts
        $virtualAccounts = VirtualAccount::where([['business_id', $bizz->id], ['status','active']])->select('id','account_number','customer_name','bank_name')->exists();

        if($virtualAccounts){
            return response()->json([
                'status' => false,
                'message' => "You have an active virtual account"
            ]);
        }

        $bizz->bvn=$input['bvn'];
        $bizz->save();

        $gender = "Male";
//        $address = $bizz->address;
        $address = "6, Ore-ofe street";
        $dob = "1990-10-10";
        $phone = preg_replace('/234/', '0', $u->phone_number, 1);
        $email = $u->email;

        if ($email == "") {
            return response()->json([
                'status' => false,
                'message' => "Kindly update your Business Email and try again"
            ]);
        }

        if ($phone == "") {
            return response()->json([
                'status' => false,
                'message' => "Kindly update your Business Phone Number and try again"
            ]);
        }

//        if ($address == "") {
//            return response()->json([
//                'status' => false,
//                'message' => "Kindly update your Business Address and try again"
//            ]);
//        }

        if (is_null($bizz->name)) {
            return response()->json([
                'status' => false,
                'message' => "Kindly update your Business Name and try again"
            ]);
        }

        if (is_null($bizz->bvn)) {
            return response()->json([
                'status' => false,
                'message' => "Kindly update your BVN and try again"
            ]);
        }

        if (strlen($bizz->bvn) != 11) {
            return response()->json([
                'status' => false,
                'message' => "Invalid BVN provided. Kindly update your BVN and try again"
            ]);
        }

        $emai = explode("@", $email);
        $email = "$emai[0]+" . rand(7, 9) . "@$emai[1]";

        $payload = '{
    "bvn": "' . $bizz->bvn . '",
    "firstname": "' . $bizz->name . '",
    "lastname": "SprintCheck",
    "address": "' . $address . '",
    "gender": "' . $gender . '",
    "email": "' . $email . '",
    "phone": "' . $phone . '",
    "dob": "' . $dob . '",
    "provider": "' . env('PAYLONY_BANK', 'gtb') . '"
}';

        Log::info("Create Paylony Account for " . $email);
        Log::info($payload);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('PAYLONY_BASEURL') . 'v1/create_account',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . env('PAYLONY_SECRET_KEY'),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

//        echo($response);

        Log::info("Paylony Response for " . $email);
        Log::info($response);

        $rep = json_decode($response, true);

        try {
            if ($rep['success']) {

                if ($rep['status'] == "08") {
                    return response()->json([
                        'status' => false,
                        'message' => "Error generating virtual account for your business. Kindly change your email address and try again"
                    ]);
                }

                $bname = match (env('PAYLONY_BANK', 'gtb')) {
                    "netbank" => "NET MFB",
                    "safehaven" => "SAFE HAVEN MFB",
                    default => strtoupper(env('PAYLONY_BANK', 'gtb')),
                };

                $v=VirtualAccountClient::create([
                    "business_id" => $bizz->id,
                    "account_reference" => $u->id,
                    "currency_code" => "NGN",
                    "customer_email" => $email,
                    "customer_phone" => $phone,
                    "customer_name" => $rep['data']['account_name'],
                    "account_number" => $rep['data']['account_number'],
                    "bank_name" => $bname,
                    "status" => "active",
                    "created_on" => Carbon::now(),
                    "reservation_reference" => $rep['data']['reference'],
                    "extra" => $response
                ]);

                return response()->json([
                    'status' => true,
                    'message' => "Business Account generated successfully!",
                    'data' => $v
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => "Error generating virtual account for your business. Retry later"
                ]);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return response()->json([
                'status' => false,
                'message' => "Error generating virtual account for your business. Retry later"
            ]);
        }
    }

}
