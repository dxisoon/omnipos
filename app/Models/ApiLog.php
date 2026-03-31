<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'service', 'endpoint', 'response_code', 'latency_ms', 'payload', 'response', 'status'
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];
}