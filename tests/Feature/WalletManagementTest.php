<?php

namespace Tests\Feature;

use App\Models\TopUpRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_top_up_request_does_not_credit_the_wallet_before_admin_approval(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.wallet'))
            ->assertOk()
            ->assertSee('Saldo akun')
            ->assertSee('Ajukan isi saldo');

        $response = $this->actingAs($user)->post(route('account.wallet.top-ups.store'), [
            'amount' => 250000,
            'transfer_reference' => 'TRF-REFERENCE-001',
        ]);

        $response->assertRedirect(route('account.wallet'))
            ->assertSessionHas('success');
        $request = TopUpRequest::query()->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame(0, $user->wallet()->value('balance'));
        $this->assertDatabaseMissing('wallet_transactions', ['top_up_request_id' => $request->id]);
    }

    public function test_admin_approval_credits_a_top_up_once_and_records_the_ledger_entry(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'is_admin' => true])->save();
        $user = User::factory()->create();
        $request = $user->topUpRequests()->create([
            'amount' => 250000,
            'transfer_reference' => 'TRF-VERIFIED-001',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.top-ups.index'))
            ->assertOk()
            ->assertSee('Verifikasi isi saldo')
            ->assertSee('TRF-VERIFIED-001');

        $this->actingAs($admin)
            ->post(route('admin.top-ups.approve', $request))
            ->assertRedirect();

        $this->post(route('admin.top-ups.approve', $request))->assertRedirect();

        $this->assertDatabaseHas('top_up_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertSame(250000, $user->wallet()->value('balance'));
        $this->assertDatabaseHas('wallet_transactions', [
            'top_up_request_id' => $request->id,
            'type' => 'top_up',
            'amount' => 250000,
            'balance_after' => 250000,
        ]);
    }

    public function test_regular_users_cannot_approve_wallet_top_ups(): void
    {
        $user = User::factory()->create();
        $request = User::factory()->create()->topUpRequests()->create([
            'amount' => 10000,
            'transfer_reference' => 'TRF-REFERENCE-002',
        ]);

        $this->actingAs($user)
            ->post(route('admin.top-ups.approve', $request))
            ->assertForbidden();

        $this->assertSame(0, $request->user->wallet()->value('balance'));
    }
}
