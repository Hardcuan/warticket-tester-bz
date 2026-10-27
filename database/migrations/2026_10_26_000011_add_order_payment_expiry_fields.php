<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('public_id')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->text('gateway_payment_url')->nullable();
            $table->timestamp('gateway_session_requested_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('late_gateway_payment_at')->nullable();
        });

        DB::table('orders')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['public_id' => (string) Str::uuid()]);
                }
            });

        DB::table('orders')
            ->where('status', 'pending')
            ->whereNull('expires_at')
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update([
                            'expires_at' => Carbon::parse($order->created_at)->addDay(),
                        ]);
                }
            });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropIndex(['gateway_transaction_id']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn([
                'public_id',
                'payment_method',
                'gateway_transaction_id',
                'gateway_payment_url',
                'gateway_session_requested_at',
                'expires_at',
                'expired_at',
                'late_gateway_payment_at',
            ]);
        });
    }
};
