<?php

namespace App\Services;

use App\Models\Kyc;
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
                CURLOPT_URL => "https://api.withmono.com/v3/lookup/nin",
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
}
