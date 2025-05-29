<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    protected $fillable = [
        'business_id',
        'account_reference',
        'currency_code',
        'customer_email',
        'customer_name',
        'customer_phone',
        'account_number',
        'bank_name',
        'status',
        'reservation_reference',
        'extra'
    ];
}
