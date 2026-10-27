<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        Ticket::firstOrCreate(
            ['name' => 'Tiket Konser'],
            [
                'price' => 500000,
                'stock' => 5,
                'description' => 'Event contoh untuk mencoba proses pemesanan.',
                'sales_starts_at' => now()->subMinute(),
                'sales_ends_at' => now()->addDays(30),
                'is_published' => true,
            ],
        );
    }
}
