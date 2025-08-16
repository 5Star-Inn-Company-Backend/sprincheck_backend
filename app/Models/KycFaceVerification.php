<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycFaceVerification extends Model
{
    protected $table="kycfacever";

    protected $guarded=[];


    protected $hidden = [
        'id',
    ];
}
