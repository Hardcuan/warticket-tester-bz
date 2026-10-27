<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TopUpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TopUpRequestController extends Controller
{
    public function index(): View
    {
        $requests = TopUpRequest::query()
            ->with(['user:id,name,email', 'reviewer:id,name'])
            ->latest()
            ->paginate(20);

        return view('admin.top-ups.index', compact('requests'));
    }

    public function approve(Request $request, TopUpRequest $topUpRequest): RedirectResponse
    {
        $approved = DB::transaction(function () use ($request, $topUpRequest): bool {
            $lockedRequest = TopUpRequest::query()
                ->lockForUpdate()
                ->findOrFail($topUpRequest->id);

            if ($lockedRequest->status !== 'pending') {
                return false;
            }

            $wallet = $lockedRequest->user->wallet()
                ->lockForUpdate()
                ->firstOrFail();
            $wallet->increment('balance', $lockedRequest->amount);
            $wallet->refresh();
            $wallet->transactions()->create([
                'top_up_request_id' => $lockedRequest->id,
                'type' => 'top_up',
                'amount' => $lockedRequest->amount,
                'balance_after' => $wallet->balance,
                'description' => 'Isi saldo melalui transfer manual',
            ]);

            $lockedRequest->forceFill([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            return true;
        }, attempts: 3);

        return $approved
            ? back()->with('success', 'Transfer diverifikasi dan saldo pengguna ditambahkan.')
            : back()->with('error', 'Permintaan ini sudah pernah ditinjau.');
    }

    public function reject(Request $request, TopUpRequest $topUpRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $rejected = DB::transaction(function () use ($request, $topUpRequest, $validated): bool {
            $lockedRequest = TopUpRequest::query()
                ->lockForUpdate()
                ->findOrFail($topUpRequest->id);

            if ($lockedRequest->status !== 'pending') {
                return false;
            }

            $lockedRequest->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => $validated['admin_note'] ?? null,
            ])->save();

            return true;
        }, attempts: 3);

        return $rejected
            ? back()->with('success', 'Permintaan isi saldo ditolak.')
            : back()->with('error', 'Permintaan ini sudah pernah ditinjau.');
    }
}
