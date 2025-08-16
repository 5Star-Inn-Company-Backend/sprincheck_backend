<?php

namespace App\Services;

use App\Models\KycDriversLicense;
use App\Models\KycFaceVerification;
use App\Models\KycNIN;
use CURLFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StarFaceService
{

    public function detectFace($image, $user_id, $type="sdk"){
        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env('STARFACE_URL','https://faceapi.5starcompany.com.ng/api')."/faces/detect_faces2/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST =>  false,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('image'=> new CURLFILE($image)),
            ));
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                Log::error( 'Error: ' . curl_error($curl));
            }

            curl_close($curl);


            Log::info("STARFACE DETECTFACE VERIFICATION");
            Log::info($response);

            $resp = json_decode($response, true);

            $ref=uniqid()."-".time();

            $k=KycFaceVerification::create([
                "user_id" => $user_id,
                "reference" => $ref,
                "source_image" => $image,
                "source" => "5STAR",
                "data" => json_encode($resp),
            ]);


            if($type == "sdk"){
                throw new \Exception('SDK not supported.');
            }else{
                return ['kyc' => $k, 'data' => $resp, 'detected' => $resp['faces_detected'] > 0] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on STARFACE DETECTFACE VERIFICATION");
            Log::info($e);

            throw new \Exception('An error occurred. Please try again later or contact support.');
        }
    }

    public function detectFaceLiveness($image, $user_id, $type="sdk"){
        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env('STARFACE_URL','https://faceapi.5starcompany.com.ng/api')."/faces/detect_liveness/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST =>  false,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('image'=> new CURLFILE($image)),
            ));
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                Log::error( 'Error: ' . curl_error($curl));
            }

            curl_close($curl);


            Log::info("STARFACE DETECTFACE VERIFICATION");
            Log::info($response);

            $resp = json_decode($response, true);

            $ref=uniqid()."-".time();

            $k=KycFaceVerification::create([
                "user_id" => $user_id,
                "reference" => $ref,
                "source_image" => $image,
                "source" => "5STAR",
                "data" => json_encode($resp),
            ]);

            if($type == "sdk"){
                throw new \Exception('SDK not supported.');
            }else{
                if(isset($resp['error'])){
                    return ['kyc' => $k, 'data' => $resp, 'detected' => 0, 'liveness' => false] ;
                }
                return ['kyc' => $k, 'data' => $resp, 'detected' => $resp['faces_detected'] > 0, 'liveness' => true] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on STARFACE DETECTFACE VERIFICATION");
            Log::info($e);

            throw new \Exception('An error occurred. Please try again later or contact support.');
        }
    }

    public function compareFace($image1,$image2, $user_id, $type="sdk"){
        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => env('STARFACE_URL','https://faceapi.5starcompany.com.ng/api')."/faces/compare_faces2/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST =>  false,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('image'=> new CURLFILE($image1), 'image2'=> new CURLFILE($image2)),
            ));
            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                Log::error( 'Error: ' . curl_error($curl));
            }
            curl_close($curl);

            Log::info("STARFACE DETECTFACE VERIFICATION");
            Log::info($response);

            $resp = json_decode($response, true);

            $ref=uniqid()."-".time();

            unset($resp['model']);
            unset($resp['detector_backend']);

            $k=KycFaceVerification::create([
                "user_id" => $user_id,
                "reference" => $ref,
                "source_image" => $image1.",".$image2,
                "source" => "5STAR",
                "data" => json_encode($resp),
            ]);

            if($type == "sdk"){
                throw new \Exception('SDK not supported.');
            }else{
                return ['kyc' => $k, 'data' => $resp, 'verified' => $resp['verified']] ;
            }

        } catch (\Exception $e) {
            Log::info("Error encountered on STARFACE COMPAREFACE VERIFICATION");
            Log::info($e);

            throw new \Exception('An error occurred. Please try again later or contact support.');
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
}
