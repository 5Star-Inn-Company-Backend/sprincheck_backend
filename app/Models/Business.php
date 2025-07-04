<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $table = 'business';

    protected $fillable = [
        'name',
        'wallet',
        'confidence_level',
        'webhook_url',
        'api_key',
        'encryption_key',
        'business_email',
        'business_phone_number',
        'business_registration_number',
        'business_address',
        'city',
        'business_description',
        'country',
        'tax_identification_number',
        'business_website',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
