<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualAccountClient extends Model
{
    use HasFactory;

    protected $table = "virtual_accounts";
    protected $fillable = ['account_reference', 'currency_code', 'customer_email', 'customer_name', 'customer_phone', 'account_number', 'bank_name', 'status', 'created_on', 'reservation_reference', 'extra', 'webhook_url', 'business_id'];

}
