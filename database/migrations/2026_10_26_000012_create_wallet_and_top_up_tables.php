<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);
            $table->timestamps();
        });

        Schema::create('top_up_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('transfer_reference', 120);
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('top_up_request_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->string('description', 255);
            $table->timestamps();

            $table->unique(['order_id', 'type']);
            $table->index(['wallet_id', 'created_at']);
        });

        DB::table('users')
            ->orderBy('id')
            ->chunkById(200, function ($users): void {
                $now = now();
                $wallets = $users->map(fn (object $user): array => [
                    'user_id' => $user->id,
                    'balance' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('wallets')->insertOrIgnore($wallets);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('top_up_requests');
        Schema::dropIfExists('wallets');
    }
};
