<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $wallet = $request->user()->wallet()->firstOrFail();
        $transactions = $wallet->transactions()
            ->with('order:id,public_id')
            ->latest()
            ->paginate(10, ['*'], 'transactions_page');
        $topUpRequests = $request->user()->topUpRequests()
            ->latest()
            ->paginate(10, ['*'], 'topups_page');
        $transferInstructions = config('services.wallet_transfer');

        return view('account.wallet', compact('wallet', 'transactions', 'topUpRequests', 'transferInstructions'));
    }

    public function requestTopUp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:10000', 'max:10000000'],
            'transfer_reference' => ['required', 'string', 'max:120'],
        ]);

        $request->user()->topUpRequests()->create([
            'amount' => $validated['amount'],
            'transfer_reference' => $validated['transfer_reference'],
            'status' => 'pending',
        ]);

        return redirect()->route('account.wallet')
            ->with('success', 'Permintaan isi saldo dikirim. Saldo bertambah setelah admin memverifikasi transfer.');
    }
}
