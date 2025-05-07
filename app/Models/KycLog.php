<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycLog extends Model
{
    protected $guarded =[];

    function nin()
    {
      return $this->belongsTo(KycNIN::class,'kycnin_id')->select('id','data');
    }

    function bvn()
    {
      return $this->belongsTo(Kyc::class,'kyc_id')->select('id','data');
    }
}
