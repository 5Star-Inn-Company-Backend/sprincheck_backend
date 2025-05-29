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
        'encryption_key'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
