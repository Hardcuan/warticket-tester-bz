<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unpaid_order_expires_once_and_returns_reserved_stock(): void
    {
        $this->freezeTime();
        $ticket = $this->createTicket(stock: 4);
        $order = $this->createOrder($ticket, expiresAt: now()->subSecond());

        $this->artisan('orders:expire-unpaid')->assertSuccessful();

        $this->assertSame(5, $ticket->fresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'expired']);

        $this->artisan('orders:expire-unpaid')->assertSuccessful();

        $this->assertSame(5, $ticket->fresh()->stock);
    }

    public function test_a_user_can_pay_a_pending_order_once_with_their_wallet_balance(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket(stock: 4);
        $order = $this->createOrder($ticket, $user);
        $user->wallet()->update(['balance' => 600000]);

        $response = $this->actingAs($user)
            ->post(route('orders.payment.wallet', $order));

        $response->assertRedirect(route('orders.payment', $order));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'payment_method' => 'wallet',
        ]);
        $this->assertSame(100000, $user->wallet()->value('balance'));
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $user->wallet->id,
            'order_id' => $order->id,
            'type' => 'order_payment',
            'amount' => 500000,
            'balance_after' => 100000,
        ]);
        $this->assertSame(4, $ticket->fresh()->stock);

        $this->actingAs($user)
            ->post(route('orders.payment.wallet', $order))
            ->assertRedirect(route('orders.payment', $order))
            ->assertSessionHas('success', 'Pesanan ini sudah berstatus lunas.');
        $this->assertSame(100000, $user->wallet()->value('balance'));
        $this->assertDatabaseCount('wallet_transactions', 1);
    }

    public function test_wallet_payment_is_refused_when_the_balance_is_too_low(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket(stock: 4);
        $order = $this->createOrder($ticket, $user);
        $user->wallet()->update(['balance' => 499999]);

        $response = $this->actingAs($user)
            ->from(route('orders.payment', $order))
            ->post(route('orders.payment.wallet', $order));

        $response->assertRedirect(route('orders.payment', $order))
            ->assertSessionHas('error', 'Saldo akun tidak mencukupi. Silakan isi saldo terlebih dahulu.');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertSame(499999, $user->wallet()->value('balance'));
        $this->assertDatabaseMissing('wallet_transactions', ['order_id' => $order->id]);
    }

    public function test_midtrans_creates_a_snap_session_using_the_stable_order_id_and_amount(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/snap-token',
            ]),
        ]);
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.snap_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
            'services.midtrans.is_production' => false,
        ]);

        $user = User::factory()->create();
        $ticket = $this->createTicket();
        $order = $this->createOrder($ticket, $user);
        $user->wallet()->update(['balance' => 600000]);

        $response = $this->actingAs($user)->post(route('orders.payment.midtrans', $order));

        $response->assertRedirect('https://app.sandbox.midtrans.com/snap/v3/redirection/snap-token');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_method' => 'midtrans',
            'gateway_payment_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/snap-token',
        ]);
        $this->actingAs($user)
            ->post(route('orders.payment.midtrans', $order))
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v3/redirection/snap-token');
        $this->actingAs($user)
            ->post(route('orders.payment.wallet', $order))
            ->assertSessionHas('error', 'Sesi Midtrans sudah diminta. Selesaikan pembayaran di gateway atau hubungi admin sebelum mencoba metode lain.');
        $this->assertSame(600000, $user->wallet()->value('balance'));
        Http::assertSent(fn ($request): bool => $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
            && $request['transaction_details']['order_id'] === $order->public_id
            && $request['transaction_details']['gross_amount'] === 500000);
        Http::assertSentCount(1);
    }

    public function test_midtrans_webhook_verifies_server_status_and_is_idempotent(): void
    {
        Http::preventStrayRequests();
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.status_url' => 'https://api.sandbox.midtrans.com/v2',
        ]);

        $ticket = $this->createTicket(stock: 4);
        $order = $this->createOrder($ticket);
        $grossAmount = '500000.00';
        $payload = [
            'order_id' => $order->public_id,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => hash('sha512', $order->public_id.'200'.$grossAmount.'sandbox-server-key'),
            'transaction_status' => 'settlement',
        ];

        Http::fake([
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => $order->public_id,
                'status_code' => '200',
                'gross_amount' => $grossAmount,
                'transaction_status' => 'settlement',
                'transaction_id' => 'midtrans-transaction-1',
            ]),
        ]);

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
            'gateway_transaction_id' => 'midtrans-transaction-1',
        ]);
        $this->assertSame(4, $ticket->fresh()->stock);
    }

    public function test_midtrans_webhook_rejects_an_invalid_signature_without_changing_the_order(): void
    {
        Http::preventStrayRequests();
        config(['services.midtrans.server_key' => 'sandbox-server-key']);

        $ticket = $this->createTicket(stock: 4);
        $order = $this->createOrder($ticket);

        $this->postJson(route('payments.midtrans.notification'), [
            'order_id' => $order->public_id,
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => 'invalid',
        ])->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertSame(4, $ticket->fresh()->stock);
    }

    public function test_midtrans_webhook_refuses_a_server_status_with_the_wrong_amount(): void
    {
        Http::preventStrayRequests();
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.status_url' => 'https://api.sandbox.midtrans.com/v2',
        ]);

        $ticket = $this->createTicket(stock: 4);
        $order = $this->createOrder($ticket);
        $grossAmount = '500000.00';
        $payload = [
            'order_id' => $order->public_id,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => hash('sha512', $order->public_id.'200'.$grossAmount.'sandbox-server-key'),
        ];

        Http::fake([
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => $order->public_id,
                'status_code' => '200',
                'gross_amount' => '499999.00',
                'transaction_status' => 'settlement',
                'transaction_id' => 'wrong-amount-transaction',
            ]),
        ]);

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertUnprocessable();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertSame(4, $ticket->fresh()->stock);
    }

    public function test_a_successful_gateway_payment_after_expiry_is_flagged_and_stock_is_released_once(): void
    {
        Http::preventStrayRequests();
        config([
            'services.midtrans.server_key' => 'sandbox-server-key',
            'services.midtrans.status_url' => 'https://api.sandbox.midtrans.com/v2',
        ]);
        $this->freezeTime();

        $ticket = $this->createTicket(stock: 4);
        $order = $this->createOrder($ticket, expiresAt: now()->subSecond());
        $grossAmount = '500000.00';
        $payload = [
            'order_id' => $order->public_id,
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'signature_key' => hash('sha512', $order->public_id.'200'.$grossAmount.'sandbox-server-key'),
        ];

        Http::fake([
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => $order->public_id,
                'status_code' => '200',
                'gross_amount' => $grossAmount,
                'transaction_status' => 'settlement',
                'transaction_id' => 'late-transaction-1',
            ]),
        ]);

        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'expired',
            'late_gateway_payment_at' => now()->toDateTimeString(),
            'gateway_transaction_id' => 'late-transaction-1',
        ]);
        $this->assertSame(5, $ticket->fresh()->stock);
    }

    private function createTicket(int $stock = 5): Ticket
    {
        return Ticket::query()->create([
            'name' => 'Konser Test',
            'price' => 500000,
            'stock' => $stock,
            'sales_starts_at' => now()->subHour(),
            'sales_ends_at' => now()->addDay(),
            'is_published' => true,
        ]);
    }

    private function createOrder(
        Ticket $ticket,
        ?User $user = null,
        ?Carbon $expiresAt = null,
    ): Order {
        $user ??= User::factory()->create();

        return $user->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 1,
            'total' => $ticket->price,
            'status' => 'pending',
            'public_id' => (string) Str::uuid(),
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]);
    }
}
