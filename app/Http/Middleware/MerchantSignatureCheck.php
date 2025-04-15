<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MerchantSignatureCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key=$request->header('signature');

        if(!$key){
            return response()->json(['success' => 0, 'message' => 'Signature is required']);
        }

        $data=$request->all();
        $business = $request->get('biz');
        unset($data['biz']);
        unset($data['user']);

        $ddata=json_encode($data);

        $signature=hash_hmac('SHA512',$ddata,$business->encryption_key);

        Log::info('Data for Signature: ' . $ddata);
        Log::info('Generated Signature: ' . $signature);
        Log::info('Received  Signature: ' . $key);


        if($signature != $key){
            return response()->json(['success' => 0, 'message' => 'Signature verification failed']);
        }


        return $next($request);
    }
}
