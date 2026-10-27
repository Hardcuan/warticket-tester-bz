<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_id',
        'ticket_name',
        'unit_price',
        'quantity',
        'total',
        'status',
        'paid_at',
        'public_id',
        'payment_method',
        'gateway_transaction_id',
        'gateway_payment_url',
        'gateway_session_requested_at',
        'expires_at',
        'expired_at',
        'late_gateway_payment_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
            'late_gateway_payment_at' => 'datetime',
            'gateway_session_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
