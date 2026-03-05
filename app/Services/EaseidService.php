<?php

namespace App\Services;

use App\Models\Kyc;
use App\Models\Kyccac;
use App\Models\KyccacDirector;
use App\Models\KyccacProfile;
use App\Models\KyccacShareholder;
use App\Models\KycDriversLicense;
use App\Models\KycNIN;
use App\Models\KycTin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\RSA;

class EaseidService
{

    public function bvn($number, $user_id, $type="sdk"){
        try {


            $payload = '{
    "requestTime":'.round(microtime(true) * 1000).',
    "bvn":"'.$number.'",
    "nonceStr":"'.Str::random(32).'",
    "version": "V1.1"
}';

// Decode JSON into an associative array
            $data = json_decode($payload, true);

            // Get the private key (how you retrieve this depends on your storage method)
            $privateKey = env('EASEID_PRIVATE_KEY');

            $sign = $this->generateSignature($data, $privateKey); // Call the sign method


            Log::info($payload);

            $resp=Http::withHeaders([
                'countryCode' => 'NG',
                'Signature' => $sign,
                'Authorization' => 'Bearer '.env('EASEID_APP_ID'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->withoutVerifying()
                ->post(env('EASEID_BASEURL').'/open/bvn/inquire',
                json_decode($payload,true))->json();

            Log::info("EASEID NIN VERIFICATION");
            Log::info($payload);
            Log::info(json_encode($resp));

        } catch (\Exception $e) {
            Log::info("Error encountered on EASEID verification on " . $number);
            Log::info($e->getMessage());

            throw new \Exception($e->getMessage());
        }

        if($resp['respCode'] != "00000000"){
            throw new \Exception('Verification Failed. '.$resp['respMsg']);
        }

        $name=$resp['data']['lastName'] . " " .$resp['data']['firstName'] . " " .$resp['data']['middleName'];

        $ref=$resp['requestId'];

        $k=Kyc::create([
            "user_id" => $user_id,
            "bvn" => $number,
            "reference" => $ref,
            "name" => $name,
            "source" => "EASEID",
            "data" => json_encode($resp['data']),
        ]);

        if($type == "sdk"){
            return  ['image' => $resp['data']['photo'], 'reference' =>$ref, 'fee' => env('EASEID_BVN_FEE',18.03)] ;
        }else{
            return ['kyc' => $k, 'data' => $resp['data'], 'fee' => env('EASEID_BVN_FEE',18.03)] ;
        }
    }

    public function nin($number, $user_id, $type="sdk"){
        try {


            $payload = '{
    "requestTime":'.round(microtime(true) * 1000).',
    "nin":"'.$number.'",
    "nonceStr":"'.Str::random(32).'",
    "version": "V1.1"
}';

// Decode JSON into an associative array
            $data = json_decode($payload, true);

            // Get the private key (how you retrieve this depends on your storage method)
            $privateKey = env('EASEID_PRIVATE_KEY');

            $sign = $this->generateSignature($data, $privateKey); // Call the sign method


            Log::info($payload);

            $resp=Http::withHeaders([
                'countryCode' => 'NG',
                'Signature' => $sign,
                'Authorization' => 'Bearer '.env('EASEID_APP_ID'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->withoutVerifying()
                ->post(env('EASEID_BASEURL').'/open/nin/inquire',
                json_decode($payload,true))->json();

            Log::info("EASEID NIN VERIFICATION");
            Log::info($payload);
            Log::info(json_encode($resp));
        } catch (\Exception $e) {
            Log::info("Error encountered on EASEID verification on " . $number);
            Log::info($e->getMessage());

            throw new \Exception($e->getMessage());
        }

        if($resp['respCode'] != "00000000"){
            throw new \Exception('Verification Failed. '.$resp['respMsg']);
        }

        $name=$resp['data']['surname'] . " " .$resp['data']['firstName'] . " " .$resp['data']['middleName'];

        $ref=$resp['requestId'];

        $k=KycNIN::create([
            "user_id" => $user_id,
            "nin" => $number,
            "reference" => $ref,
            "name" => $name,
            "source" => "EASEID",
            "data" => json_encode($resp['data']),
        ]);

        if($type == "sdk"){
            return  ['image' => $resp['data']['photo'], 'reference' =>$ref, 'fee' => env('EASEID_NIN_FEE',36.05)];
        }else{
            return ['kyc' => $k, 'data' => $resp['data'], 'fee' => env('EASEID_NIN_FEE',36.05)] ;
        }
    }

    private function generateSignature($data, $privateKey)
    {
        try {
            // Step 1: Sort the parameters in ASCII order and format as a query string
            $filteredData = [];
            foreach ($data as $key => $value) {
                if (!empty($value)) {
                    $filteredData[$key] = trim($value);
                }
            }

            // Sort keys in ASCII order
            ksort($filteredData);

            // Convert to key=value format
            $strA = urldecode(http_build_query($filteredData, '', '&', PHP_QUERY_RFC3986));

            // Step 2: Perform MD5 operation and convert to uppercase
            $md5Str = strtoupper(md5($strA));

            // Step 3: Load the private key
            $privateKey = RSA::loadPrivateKey($privateKey);

            // Sign using SHA1WithRSA
            $signature = $privateKey
                ->withPadding(RSA::SIGNATURE_PKCS1)
                ->withHash('sha1')
                ->sign($md5Str);

            // Return the Base64-encoded signature
            return base64_encode($signature);
        } catch (\Exception $e) {
            throw new \Exception('Error generating signature: ' . $e->getMessage());
        }

    }

}
