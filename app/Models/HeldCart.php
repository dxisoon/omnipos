<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeldCart extends Model
{
    protected $fillable = ['user_id', 'label', 'cart_data'];

    protected $casts = [
        'cart_data' => 'array',
    ];
}