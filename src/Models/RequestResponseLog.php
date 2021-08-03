<?php

namespace Mtownsend\RequestResponseLogger\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mtownsend\RequestResponseLogger\Casts\ArrayOrText;

class RequestResponseLog extends Model
{
    use HasFactory;

    public $guarded = [
        'id'
    ];

    public $casts = [
        'request_headers' => ArrayOrText::class,
        'response_headers' => ArrayOrText::class,
        'request_body' => ArrayOrText::class,
        'response_body' => ArrayOrText::class,
    ];

    /**
     * Scope a query to only include failed http code responses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('response_http_code', 'NOT LIKE', 2 . '%');
    }

    /**
     * Scope a query to only include successful http code responses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('response_http_code', 'LIKE', 2 . '%');
    }
}
