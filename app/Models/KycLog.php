<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycLog extends Model
{
    protected $fillable = [
      'business_id',
      'type',
      'identifier',
      'user_id',
      'billing_id',
      'kycnin_id',
      'kyc_id',
      'status',
      'data',
      'source',
      'confidence',
      'image'
    ];

    function nin()
    {
      return $this->belongsTo(KycNIN::class,'kyc_id')->select('id','data');
    }

    function bvn()
    {
      return $this->belongsTo(Kyc::class,'kyc_id')->select('id','data');
    }
}
