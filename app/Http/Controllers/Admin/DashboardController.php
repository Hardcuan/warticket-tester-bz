<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeSince = now()->subMinutes(5);
        $metrics = [
            'users' => User::query()->where('is_active', true)->count(),
            'active_users' => User::query()->where('is_active', true)->where('last_seen_at', '>=', $activeSince)->count(),
            'events' => Ticket::query()->count(),
            'orders' => Order::query()->whereIn('status', ['pending', 'paid'])->count(),
            'expired_orders' => Order::query()->where('status', 'expired')->count(),
            'tickets_sold' => Order::query()->whereIn('status', ['pending', 'paid'])->sum('quantity'),
            'revenue' => Order::query()->where('status', 'paid')->sum('total'),
        ];

        $popularTickets = Ticket::query()
            ->withSum(['orders as tickets_ordered' => fn ($query) => $query->whereIn('status', ['pending', 'paid'])], 'quantity')
            ->orderByDesc('tickets_ordered')
            ->limit(5)
            ->get();

        $activeUsers = User::query()
            ->where('is_active', true)
            ->where('last_seen_at', '>=', $activeSince)
            ->latest('last_seen_at')
            ->limit(8)
            ->get(['id', 'name', 'email', 'last_seen_at']);

        $frequentUsers = User::query()
            ->withCount(['orders as orders_count' => fn ($query) => $query->whereIn('status', ['pending', 'paid'])])
            ->withSum(['orders as tickets_ordered' => fn ($query) => $query->whereIn('status', ['pending', 'paid'])], 'quantity')
            ->whereHas('orders', fn ($query) => $query->whereIn('status', ['pending', 'paid']))
            ->orderByDesc('orders_count')
            ->limit(8)
            ->get(['id', 'name', 'email', 'last_seen_at']);

        $recentOrders = Order::query()
            ->with('user:id,name,email')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('metrics', 'popularTickets', 'activeUsers', 'frequentUsers', 'recentOrders'));
    }
}
