<?php

namespace App\Services;

use App\Models\Kyc;
use App\Models\KycNIN;
use App\Models\KycPassport;
use App\Models\KycVoters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PremblyService
{

    public function bvn($number, $user_id, $type="sdk"){
        try {

            $payload= '{
                "number":"'.$number.'"
            }';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env("PREMBLY_URL", 'https://api.prembly.com') . "/identitypass/verification/bvn",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$payload,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => array(
                    "x-api-key: " . env("PREMBLY_KEY"),
                    "app-id: " . env("PREMBLY_APPID"),
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);

            Log::info("PREMBLY VERIFICATION");
            Log::info($payload);
            Log::info($response);

            $resp = json_decode($response, true);

            if(!$resp['status']){
                throw new \Exception('Verification Failed. Kindly provide valid BVN');
            }

            $name=$resp['data']['lastName'] . " " .$resp['data']['firstName'] . " " .$resp['data']['middleName'];
            $k=Kyc::create([
                "user_id" => $user_id,
                "bvn" => $number,
                "reference" => $resp['verification']['reference'],
                "name" => $name,
                "data" => json_encode($resp['data']),
            ]);

            if($type == "sdk"){
                return  ['image' => $resp['data']['base64Image'], 'reference' =>$resp['verification']['reference']];
            }else{
                return ['kyc' => $k, 'data' => $resp['data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Prembly account verification on " . $number);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }

    public function nin($number, $user_id, $type="sdk"){
        try {

            $payload= '{
                "number":"'.$number.'"
            }';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env("PREMBLY_URL", 'https://api.prembly.com') . "/identitypass/verification/vnin",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$payload,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => array(
                    "x-api-key: " . env("PREMBLY_KEY"),
                    "app-id: " . env("PREMBLY_APPID"),
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);

            Log::info("PREMBLY VERIFICATION");
            Log::info($payload);
            Log::info($response);

            $resp = json_decode($response, true);

            if(!$resp['status']){
                throw new \Exception('Verification Failed. Kindly provide valid NIN');
            }

            $name=$resp['nin_data']['surname'] . " " .$resp['nin_data']['firstname'] . " " .$resp['nin_data']['middlename'];
            $k=KycNIN::create([
                "user_id" => $user_id,
                "nin" => $number,
                "reference" => $resp['verification']['reference'],
                "name" => $name,
                "data" => json_encode($resp['nin_data']),
            ]);

            if($type == "sdk"){
                return  ['image' => $resp['nin_data']['photo'], 'reference' =>$resp['verification']['reference']];
            }else{
                return ['kyc' => $k, 'data' => $resp['nin_data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Prembly account verification on " . $number);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }
    public function passport($number, $dob, $user_id, $type="sdk"){
        try {

            $payload= '{
                "number":"'.$number.'",
                "last_name": "ODEJINMI",
                "dob":"'.$dob.'"
            }';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env("PREMBLY_URL", 'https://api.prembly.com') . "/identitypass/verification/national_passport_v2",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$payload,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => array(
                    "x-api-key: " . env("PREMBLY_KEY"),
                    "app-id: " . env("PREMBLY_APPID"),
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);

            Log::info("PREMBLY VERIFICATION");
            Log::info($payload);
            Log::info($response);

            $resp = json_decode($response, true);

            if(!$resp['status']){
                throw new \Exception('Verification Failed. Kindly provide valid Details for Passport');
            }

            $name=$resp['data']['currentLastName'] . " " .$resp['data']['currentFirstName'] . " " .$resp['data']['currentMiddleName'];
            $k=KycPassport::create([
                "user_id" => $user_id,
                "number" => $number,
                "reference" => $resp['verification']['reference'],
                "name" => $name,
                "data" => json_encode($resp['data']),
            ]);

            if($type == "sdk"){
                throw new \Exception('SDK not supported.');
            }else{
                return ['kyc' => $k, 'data' => $resp['data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Prembly account verification on " . $number);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }

    public function voters($number,$user_id, $type="sdk"){
        try {

            $payload= '{
                "number":"'.$number.'"
            }';

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env("PREMBLY_URL", 'https://api.prembly.com') . "/identitypass/verification/voters_card",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$payload,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => array(
                    "x-api-key: " . env("PREMBLY_KEY"),
                    "app-id: " . env("PREMBLY_APPID"),
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);

            curl_close($curl);

            Log::info("PREMBLY VERIFICATION");
            Log::info($payload);
            Log::info($response);

            $resp = json_decode($response, true);

            if(!$resp['status']){
                throw new \Exception('Verification Failed. Kindly provide valid Details');
            }

            $name=$resp['data']['fullName'];
            $k=KycVoters::create([
                "user_id" => $user_id,
                "number" => $number,
                "reference" => $resp['verification']['reference'],
                "name" => $name,
                "data" => json_encode($resp['data']),
            ]);

            if($type == "sdk"){
                return  ['image' => $resp['data']['photo'], 'reference' =>$resp['verification']['reference']];
            }else{
                return ['kyc' => $k, 'data' => $resp['data']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on Prembly account verification on " . $number);
            Log::info($e);

            throw new \Exception('Unable to verify try again later.');
        }
    }
}
