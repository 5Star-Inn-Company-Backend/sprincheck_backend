<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionFee extends Model
{
    function getTransactionFee($business_id, $service){
        $t1=TransactionFee::where(["business_id" => $business_id, "service" => $service, 'status' =>1])->first();

        if(!$t1){
            $t0=TransactionFee::where(["business_id" => 0, "service" => $service, 'status' =>1])->first();
            $fee=$t0->fee;
        }else{
            $fee=$t1->fee;
        }

        return round($fee,2);
    }

}
