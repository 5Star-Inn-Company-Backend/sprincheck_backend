<?php

namespace App\Services;

use App\Models\Kyc;
use App\Models\Kyccac;
use App\Models\KyccacShareholder;
use App\Models\KycDriversLicense;
use App\Models\KycNIN;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MonoService
{

    public function nin($number, $user_id, $type="sdk"){
        try {

            $payload= '{
                "nin":"'.$number.'"
            }';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env("MOMO_URL", 'https://api.withmono.com')."/v3/lookup/nin",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
//                CURLOPT_HEADER => 1,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$payload,
                CURLOPT_HTTPHEADER => array(
                    'mono-sec-key:' . env('MONO_SECRETKEY'),
                    'Accept: application/json',
                    'Content-Type: application/json'
                ),
            ));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($curl);

            curl_close($curl);


            Log::info("MONO VERIFICATION");
            Log::info($payload);
            Log::info($response);

            $resp = json_decode($response, true);

            if($resp['status'] != "successful"){
                throw new \Exception('Verification Failed. Kindly provide valid NIN');
            }

            $name=$resp['data']['surname'] . " " .$resp['data']['firstname'] . " " .$resp['data']['middlename'];

            $ref=uniqid()."-".time();

            $k=KycNIN::create([
                "user_id" => $user_id,
                "nin" => $number,
                "reference" => $ref,
                "name" => $name,
                "source" => "MONO",
                "data" => json_encode($resp['data']),
            ]);

            if($type == "sdk"){
                return  ['image' => $resp['data']['photo'], 'reference' =>$ref];
            }else{
                return ['kyc' => $k, 'data' => $resp['data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Mono account verification on " . $number);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }
    public function drivers_license($number, $dob,$fname,$lname, $user_id, $type="sdk"){
        try {

            $payload= '{
                "license_number":"'.$number.'",
                  "last_name": "'.$lname.'",
                  "first_name":"'.$fname.'",
                  "date_of_birth": "'.$dob.'"
            }';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env("MOMO_URL", 'https://api.withmono.com')."/v3/lookup/driver_license",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
//                CURLOPT_HEADER => 1,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$payload,
                CURLOPT_HTTPHEADER => array(
                    'mono-sec-key:' . env('MONO_SECRETKEY'),
                    'Accept: application/json',
                    'Content-Type: application/json'
                ),
            ));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($curl);

            curl_close($curl);


            Log::info("MONO VERIFICATION");
            Log::info($payload);
            Log::info($response);

            $resp = json_decode($response, true);

            if($resp['status'] != "successful"){
                throw new \Exception('Verification Failed. Kindly provide valid Details');
            }

            $name=$resp['data']['last_name'] . " " .$resp['data']['first_name'];

            $ref=uniqid()."-".time();

            $k=KycDriversLicense::create([
                "user_id" => $user_id,
                "number" => $number,
                "reference" => $ref,
                "name" => $name,
                "source" => "MONO",
                "data" => json_encode($resp['data']),
            ]);

            if($type == "sdk"){
                throw new \Exception('SDK not supported.');
            }else{
                return ['kyc' => $k, 'data' => $resp['data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Mono account verification on " . $number);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }

    public function cacName($name, $user_id, $type="sdk"){


        $url=env("MOMO_URL", 'https://api.withmono.com')."/v3/lookup/cac?search=".urlencode($name);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
//                CURLOPT_HEADER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'mono-sec-key:' . env('MONO_SECRETKEY'),
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($curl);

        curl_close($curl);


        Log::info("CAC MONO VERIFICATION");
        Log::info($url);
        Log::info($response);

        if($response == null){
            throw new \Exception("Search currently not available. Kindly try again or reach out to customer support if issue persists.");
        }

        $resp = json_decode($response, true);

        if($resp['status'] != "successful"){
            throw new \Exception("We couldn't find any matching records based on the information you provided. Please double-check the parameters you passed and try again");
        }

        try {
            $ref=uniqid()."-".time();

            $k=Kyccac::create([
                "user_id" => $user_id,
                "reference" => $ref,
                "name" => $name,
                "source" => "MONO",
                "data" => json_encode($resp['data']),
            ]);

            if($type == "sdk"){
                throw new \Exception('SDK not supported.');
            }else{
                return ['kyc' => $k, 'data' => $resp['data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Mono account verification on " . $name);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }

    public function cacShareHolders($bizID, $user_id, $type="sdk"){


        $url=env("MOMO_URL", 'https://api.withmono.com')."/v3/lookup/cac/company/$bizID";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
//                CURLOPT_HEADER => 1,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'mono-sec-key:' . env('MONO_SECRETKEY'),
                'Accept: application/json',
                'Content-Type: application/json'
            ),
        ));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($curl);

        curl_close($curl);


        Log::info("CAC_SHAREHOLDERS MONO VERIFICATION");
        Log::info($url);
        Log::info($response);

        if($response == null){
            throw new \Exception("Search currently not available. Kindly try again or reach out to customer support if issue persists.");
        }


        $resp = json_decode($response, true);

            if($resp['status'] != "successful"){
                throw new \Exception("We couldn't find any matching records based on the information you provided. Please double-check the parameters you passed and try again");
            }

        try {
            $ref=uniqid()."-".time();

            $k=KyccacShareholder::create([
                "user_id" => $user_id,
                "reference" => $ref,
                "biz_id" => $bizID,
                "source" => "MONO",
                "data" => json_encode($resp['data']),
            ]);

            if($type == "sdk"){
                throw new \Exception('SDK not supported.');
            }else{
                return ['kyc' => $k, 'data' => $resp['data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Mono cacShareHolders verification on " . $bizID);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }
}
