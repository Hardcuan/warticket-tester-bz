<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'sales_starts_at',
        'sales_ends_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'sales_starts_at' => 'datetime',
            'sales_ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isOnSale(): bool
    {
        $now = now();

        return $this->is_published
            && $this->sales_starts_at !== null
            && $this->sales_ends_at !== null
            && $now->greaterThanOrEqualTo($this->sales_starts_at)
            && $now->lessThanOrEqualTo($this->sales_ends_at);
    }

    public function saleStatus(): string
    {
        if (
            ! $this->is_published
            || $this->sales_starts_at === null
            || $this->sales_ends_at === null
        ) {
            return 'unavailable';
        }

        if (now()->greaterThan($this->sales_ends_at)) {
            return 'ended';
        }

        if (now()->lessThan($this->sales_starts_at)) {
            return 'upcoming';
        }

        if ($this->stock < 1) {
            return 'sold_out';
        }

        return 'open';
    }
}
