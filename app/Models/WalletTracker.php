<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTracker extends Model
{
    protected $table='wallet_tracker';

    protected $fillable = [
        'business_id', 'reference', 'amount', 'description', 'type', 'pre_wallet', 'post_wallet'
    ];
}
