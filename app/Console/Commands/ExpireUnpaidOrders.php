<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderReservationService;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Expire unpaid ticket reservations and release their stock';

    public function handle(OrderReservationService $reservations): int
    {
        Order::query()
            ->where('status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '<=', now());
            })
            ->select('id')
            ->chunkById(100, function ($orders) use ($reservations): void {
                foreach ($orders as $order) {
                    $reservations->expire($order);
                }
            });

        $this->components->info('Expired unpaid reservations were processed.');

        return self::SUCCESS;
    }
}
