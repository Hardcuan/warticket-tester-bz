<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class WarAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_but_cannot_grant_themselves_admin_access(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Peserta',
            'email' => 'peserta@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertFalse(User::query()->where('email', 'peserta@example.test')->firstOrFail()->is_admin);
    }

    public function test_captcha_requires_login_and_an_open_ticket_sale(): void
    {
        $ticket = $this->createTicket(salesStart: now()->addHour());

        $this->get(route('captcha.image', $ticket))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('captcha.image', $ticket))
            ->assertForbidden();
    }

    public function test_logged_in_user_gets_a_session_bound_png_captcha_when_ticket_sale_is_open(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket(salesStart: now()->subMinute());

        $response = $this->actingAs($user)->get(route('captcha.image', $ticket));
        $imageSize = getimagesizefromstring($response->getContent());

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0')
            ->assertSessionHas('captcha_user_id', $user->id)
            ->assertSessionHas('captcha_ticket_id', $ticket->id)
            ->assertSessionHas('captcha_text');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertIsArray($imageSize);
        $this->assertSame(300, $imageSize[0]);
        $this->assertSame(88, $imageSize[1]);
        $this->assertSame(IMAGETYPE_PNG, $imageSize[2]);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{6}$/', session('captcha_text'));
    }

    public function test_generated_test_captcha_can_complete_the_checkout_flow(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket(stock: 2, salesStart: now()->subMinute());

        $captchaResponse = $this->actingAs($user)->get(route('captcha.image', $ticket));

        $captchaResponse->assertOk()->assertSessionHas('captcha_text', 'QATEST');

        $response = $this->post(route('tickets.buy', $ticket), [
            'quantity' => 1,
            'captcha' => 'QATEST',
        ]);

        $order = $user->orders()->firstOrFail();
        $response->assertRedirect(route('orders.payment', $order));
        $this->assertSame(1, $ticket->fresh()->stock);
        $this->assertSame('pending', $order->status);
    }

    public function test_captcha_form_accepts_lowercase_letters(): void
    {
        $user = User::factory()->create();
        $this->createTicket(salesStart: now()->subMinute());

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('pattern="[A-HJ-NP-Za-hj-np-z2-9]{6}"', false);
    }

    public function test_checkout_quantity_is_rendered_as_a_slot_dropdown(): void
    {
        $user = User::factory()->create([
            'name' => 'Peserta War',
            'email' => 'peserta@example.test',
            'phone' => '+628123456789',
        ]);
        $this->createTicket(stock: 5);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('<form id="form_pendaftaran" method="POST" action="#" class="d-grid gap-3">', false)
            ->assertSee('id="nama_lengkap" type="text" class="form-control" value="Peserta War" readonly', false)
            ->assertSee('id="email" type="email" class="form-control" value="peserta@example.test" readonly', false)
            ->assertSee('id="nomor_telepon" type="tel" class="form-control" value="+628123456789" readonly', false)
            ->assertSee('<select id="jumlah_slot" name="quantity" class="form-select" required disabled>', false)
            ->assertSee('id="kode_verifikasi"', false)
            ->assertSee('id="btn_kirim" class="btn btn-primary" disabled>Registrasi</button>', false)
            ->assertSee('id="status_form"', false)
            ->assertSee('<option value="" selected disabled>Jumlah slot</option>', false)
            ->assertSee('id="captchaImage" src="" width="240" height="88" alt="Captcha"', false)
            ->assertSee('placeholder="Masukkan teks di atas"', false)
            ->assertDontSee('<input id="jumlah_slot" name="quantity" type="number"', false);
    }

    public function test_ticket_status_tracks_upcoming_open_ended_and_sold_out_sales(): void
    {
        $this->freezeTime();
        $upcoming = $this->createTicket(salesStart: now()->addMinute());
        $open = $this->createTicket(salesStart: now()->subMinute());
        $ended = $this->createTicket(salesStart: now()->subHours(2), salesEnd: now()->subSecond());
        $soldOut = $this->createTicket(stock: 0, salesStart: now()->subMinute());

        $this->getJson(route('tickets.stock', $upcoming))
            ->assertOk()
            ->assertJsonPath('status', 'upcoming')
            ->assertJsonPath('available', false);
        $this->getJson(route('tickets.stock', $open))
            ->assertOk()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('available', true);
        $this->getJson(route('tickets.stock', $ended))
            ->assertOk()
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('available', false);
        $this->getJson(route('tickets.stock', $soldOut))
            ->assertOk()
            ->assertJsonPath('status', 'sold_out')
            ->assertJsonPath('available', false);
    }

    public function test_registration_summary_counts_open_orders_and_paid_tickets_without_exposing_names(): void
    {
        $ticket = $this->createTicket(stock: 0, salesStart: now()->subHour());
        $user = User::factory()->create(['name' => 'Nama Rahasia']);
        $user->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 2,
            'total' => 1000000,
            'status' => 'pending',
            'public_id' => (string) Str::uuid(),
            'expires_at' => now()->addHours(2),
        ]);
        $user->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 1,
            'total' => 500000,
            'status' => 'paid',
            'public_id' => (string) Str::uuid(),
            'expires_at' => now()->addDay(),
        ]);
        $user->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 4,
            'total' => 2000000,
            'status' => 'expired',
            'public_id' => (string) Str::uuid(),
            'expires_at' => now()->subMinute(),
        ]);

        $this->getJson(route('tickets.registrations', $ticket))
            ->assertOk()
            ->assertJsonPath('registered_count', 3)
            ->assertJsonPath('pending_payment_count', 2)
            ->assertJsonPath('paid_count', 1)
            ->assertJsonMissing(['name' => 'Nama Rahasia'])
            ->assertJsonMissing(['email' => $user->email]);
    }

    public function test_registration_summary_is_not_available_for_unpublished_events(): void
    {
        $ticket = $this->createTicket();
        $ticket->forceFill(['is_published' => false])->save();

        $this->getJson(route('tickets.registrations', $ticket))->assertNotFound();
    }

    public function test_registration_summary_is_only_available_after_the_event_is_sold_out_or_ended(): void
    {
        $upcoming = $this->createTicket(salesStart: now()->addHour());

        $this->getJson(route('tickets.registrations', $upcoming))->assertForbidden();
    }

    public function test_upcoming_event_modal_displays_a_countdown_without_loading_captcha_early(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket(salesStart: now()->addMinute());

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Lihat hitung mundur')
            ->assertSee('War dimulai dalam')
            ->assertSee('captchaImage')
            ->assertSee('data-captcha-url="'.route('captcha.image', $ticket).'"', false)
            ->assertSee('id="captchaSection" class="d-grid gap-2 d-none"', false)
            ->assertSee('id="captchaImage" src=""', false)
            ->assertSee('id="btn_kirim" class="btn btn-primary" disabled>Registrasi</button>', false);
    }

    public function test_ended_events_are_rendered_as_unavailable_in_the_catalog(): void
    {
        $ticket = $this->createTicket(salesStart: now()->subHours(2), salesEnd: now()->subSecond());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sudah berakhir')
            ->assertSee('data-status="ended"', false)
            ->assertSee('data-registrations-url="'.route('tickets.registrations', $ticket).'"', false)
            ->assertSee('id="registeredSummary"', false);
    }

    public function test_a_valid_captcha_can_order_multiple_tickets_with_a_price_snapshot(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket(stock: 3, salesStart: now()->subMinute());

        $response = $this->actingAs($user)
            ->withSession([
                'captcha_text' => 'ABC234',
                'captcha_user_id' => $user->id,
                'captcha_ticket_id' => $ticket->id,
            ])
            ->post(route('tickets.buy', $ticket), [
                'quantity' => 2,
                'captcha' => 'abc234',
            ]);

        $order = $user->orders()->firstOrFail();
        $response->assertRedirect(route('orders.payment', $order));
        $this->assertSame(1, $ticket->fresh()->stock);
        $this->assertSame(1000000, $order->total);
        $this->assertSame(2, $order->quantity);
        $response->assertSessionMissing('captcha_text');
    }

    public function test_captcha_from_another_user_or_ticket_cannot_be_used(): void
    {
        $captchaOwner = User::factory()->create();
        $buyer = User::factory()->create();
        $ticket = $this->createTicket(stock: 2, salesStart: now()->subMinute());

        $response = $this->actingAs($buyer)
            ->withSession([
                'captcha_text' => 'ABC234',
                'captcha_user_id' => $captchaOwner->id,
                'captcha_ticket_id' => $ticket->id,
            ])
            ->post(route('tickets.buy', $ticket), [
                'quantity' => 1,
                'captcha' => 'ABC234',
            ]);

        $response->assertSessionHasErrors('captcha');
        $this->assertSame(2, $ticket->fresh()->stock);
    }

    public function test_purchase_after_sales_close_is_rejected_without_changing_stock(): void
    {
        $user = User::factory()->create();
        $ticket = $this->createTicket(stock: 2, salesEnd: now()->subMinute());

        $response = $this->actingAs($user)
            ->post(route('tickets.buy', $ticket), [
                'quantity' => 1,
                'captcha' => 'ABC234',
            ]);

        $response->assertSessionHas('error');
        $this->assertSame(2, $ticket->fresh()->stock);
    }

    public function test_a_user_cannot_view_another_users_order_payment(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = $this->createTicket();
        $order = $owner->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 1,
            'total' => $ticket->price,
            'status' => 'pending',
            'public_id' => (string) Str::uuid(),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($otherUser)
            ->get(route('orders.payment', $order))
            ->assertNotFound();
    }

    public function test_order_owner_can_view_payment_receipt(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->createTicket();
        $order = $owner->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 1,
            'total' => $ticket->price,
            'status' => 'pending',
            'public_id' => (string) Str::uuid(),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('orders.payment', $order))
            ->assertOk()
            ->assertSee('Ringkasan pembayaran')
            ->assertSee('Konser Test');
    }

    public function test_admin_can_access_ticket_management_but_regular_users_cannot(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.tickets.index'))
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.tickets.index'))
            ->assertOk();
    }

    private function createTicket(
        int $stock = 5,
        ?Carbon $salesStart = null,
        ?Carbon $salesEnd = null,
    ): Ticket {
        return Ticket::query()->create([
            'name' => 'Konser Test',
            'price' => 500000,
            'stock' => $stock,
            'sales_starts_at' => $salesStart ?? now()->subMinute(),
            'sales_ends_at' => $salesEnd ?? now()->addDay(),
            'is_published' => true,
        ]);
    }
}
