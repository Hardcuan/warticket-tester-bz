<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? null;

        $users = User::query()
            ->withCount(['orders as orders_count' => fn ($query) => $query->whereIn('status', ['pending', 'paid'])])
            ->withSum(['orders as tickets_ordered' => fn ($query) => $query->whereIn('status', ['pending', 'paid'])], 'quantity')
            ->when($search, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('last_seen_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->forceFill([
            'role' => $validated['role'],
            'is_admin' => $validated['role'] === 'admin',
        ])->save();

        return back()->with('success', 'Akun pengguna berhasil ditambahkan.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Role akun sendiri tidak dapat diubah.');

        $validated = $request->validate([
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);

        $updated = DB::transaction(function () use ($user, $validated): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $activeAdmins = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            if (
                $lockedUser->is_active
                &&
                $lockedUser->role === 'admin'
                && $validated['role'] !== 'admin'
                && $activeAdmins->count() <= 1
            ) {
                return false;
            }

            $lockedUser->forceFill([
                'role' => $validated['role'],
                'is_admin' => $validated['role'] === 'admin',
            ])->save();

            return true;
        }, attempts: 3);

        if (! $updated) {
            return back()->withErrors(['role' => 'Admin aktif terakhir tidak dapat diturunkan rolenya.']);
        }

        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');

        $updated = DB::transaction(function () use ($user): string {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $activeAdmins = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            if (
                $lockedUser->is_active
                && $lockedUser->role === 'admin'
                && $activeAdmins->count() <= 1
            ) {
                return 'last_admin';
            }

            $lockedUser->forceFill(['is_active' => ! $lockedUser->is_active])->save();

            return $lockedUser->is_active ? 'active' : 'inactive';
        }, attempts: 3);

        if ($updated === 'last_admin') {
            return back()->withErrors(['user' => 'Admin aktif terakhir tidak dapat dinonaktifkan.']);
        }

        return back()->with('success', $updated === 'active'
            ? 'Akun berhasil diaktifkan.'
            : 'Akun berhasil dinonaktifkan; riwayat tetap tersimpan.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Akun yang sedang digunakan tidak dapat dihapus.');

        $result = DB::transaction(function () use ($user): string {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $activeAdmins = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            if (
                $lockedUser->is_active
                && $lockedUser->role === 'admin'
                && $activeAdmins->count() <= 1
            ) {
                return 'last_admin';
            }

            $hasHistory = $lockedUser->orders()->exists()
                || $lockedUser->topUpRequests()->exists()
                || $lockedUser->wallet()->whereHas('transactions')->exists();

            if ($hasHistory) {
                $lockedUser->forceFill(['is_active' => false])->save();

                return 'deactivated';
            }

            $lockedUser->delete();

            return 'deleted';
        }, attempts: 3);

        if ($result === 'last_admin') {
            return back()->withErrors(['user' => 'Admin aktif terakhir tidak dapat dihapus.']);
        }

        if ($result === 'deactivated') {
            return back()->with('success', 'Akun memiliki riwayat transaksi, jadi dinonaktifkan tanpa menghapus riwayat.');
        }

        return back()->with('success', 'Akun tanpa riwayat transaksi berhasil dihapus.');
    }
}
