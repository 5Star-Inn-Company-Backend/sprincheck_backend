<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MerchantApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key=$request->header('Authorization');

        if(!$key){
            return response()->json(['success' => 0, 'message' => 'Merchant access required']);
        }

        $biz=Business::where('api_key',$key)->first();
        if(!$biz){
            return response()->json(['success' => 0, 'message' => 'Invalid merchant access']);
        }

        $user=User::where('business_id',$biz->id)->first();

        $request->merge([
            'user' => $user,
            'biz' => $biz,
        ]);


        return $next($request);
    }
}
