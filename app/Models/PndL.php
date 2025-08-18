<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PndL extends Model
{
    use HasFactory;

    protected $table = 'tbl_p_nd_l';

    protected $fillable = [
        'type', 'amount', 'narration', 'date', 'gl'
    ];
}
