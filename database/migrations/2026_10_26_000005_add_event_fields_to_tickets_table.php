<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->dateTime('sales_starts_at')->nullable();
            $table->dateTime('sales_ends_at')->nullable();
            $table->boolean('is_published')->default(false);
        });

        DB::table('tickets')->update([
            'description' => 'Event contoh yang dipindahkan ke katalog war tiket.',
            'sales_starts_at' => now()->subMinute(),
            'sales_ends_at' => now()->addDays(30),
            'is_published' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['description', 'sales_starts_at', 'sales_ends_at', 'is_published']);
        });
    }
};
