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
      'image',
      'reference'
    ];

    function nin()
    {
      return $this->belongsTo(KycNIN::class,'kyc_id')->select('id','data');
    }

    function bvn()
    {
      return $this->belongsTo(Kyc::class,'kyc_id')->select('id','data');
    }

    function dlicense()
    {
      return $this->belongsTo(KycDriversLicense::class,'kyc_id')->select('id','data');
    }
    function passport()
    {
      return $this->belongsTo(KycPassport::class,'kyc_id')->select('id','data');
    }
    function voters()
    {
      return $this->belongsTo(KycVoters::class,'kyc_id')->select('id','data');
    }
    function facevers()
    {
      return $this->belongsTo(KycFaceVerification::class,'kyc_id')->select('id','data');
    }

    function facial()
    {
      return $this->belongsTo(KycFace::class,'kyc_id')->select('id','source_image');
    }
}
