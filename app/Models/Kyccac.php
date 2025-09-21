<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kyccac extends Model
{

    protected $guarded=[];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    protected $hidden = [
        'id',
    ];
}
