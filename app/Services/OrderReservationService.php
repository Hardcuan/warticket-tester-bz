<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class OrderReservationService
{
    public function expire(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->find($order->id);

            if (
                $lockedOrder === null
                || $lockedOrder->status !== 'pending'
                || ($lockedOrder->expires_at !== null && $lockedOrder->expires_at->isFuture())
            ) {
                return false;
            }

            $this->expireLocked($lockedOrder);

            return true;
        }, attempts: 3);
    }

    public function expireLocked(Order $order): void
    {
        $ticket = Ticket::query()
            ->lockForUpdate()
            ->findOrFail($order->ticket_id);

        $ticket->increment('stock', $order->quantity);

        $order->forceFill([
            'status' => 'expired',
            'expired_at' => now(),
        ])->save();
    }
}
