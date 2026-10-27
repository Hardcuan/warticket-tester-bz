<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\WarTicketAnnounced;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTicketManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_an_event_and_email_subscribed_users(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $recipient = User::factory()->create(['email_notifications' => true]);
        $unsubscribed = User::factory()->create(['email_notifications' => false]);

        $startsAt = now()->addDay()->startOfMinute();
        $endsAt = now()->addDays(2)->startOfMinute();

        $response = $this->actingAs($admin)->post(route('admin.tickets.store'), [
            'name' => 'Konser Akhir Tahun',
            'description' => 'Tiket konser spesial.',
            'price' => 750000,
            'stock' => 25,
            'sales_starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'sales_ends_at' => $endsAt->format('Y-m-d\TH:i'),
            'is_published' => true,
        ]);

        $ticket = Ticket::query()->where('name', 'Konser Akhir Tahun')->firstOrFail();
        $response->assertRedirect(route('admin.tickets.index'));
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'price' => 750000,
            'stock' => 25,
            'is_published' => true,
        ]);
        Notification::assertSentTo($recipient, WarTicketAnnounced::class);
        Notification::assertNotSentTo($unsubscribed, WarTicketAnnounced::class);
        Notification::assertNotSentTo($admin, WarTicketAnnounced::class);
    }

    public function test_admin_cannot_create_event_when_sales_end_is_before_start(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $startsAt = now()->addDay()->startOfMinute();

        $response = $this->actingAs($admin)->post(route('admin.tickets.store'), [
            'name' => 'Jadwal Tidak Valid',
            'price' => 500000,
            'stock' => 5,
            'sales_starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'sales_ends_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
            'is_published' => true,
        ]);

        $response->assertSessionHasErrors('sales_ends_at');
        $this->assertDatabaseMissing('tickets', ['name' => 'Jadwal Tidak Valid']);
    }

    public function test_admin_can_open_create_and_edit_event_forms(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $ticket = Ticket::query()->create([
            'name' => 'Konser Test',
            'price' => 500000,
            'stock' => 5,
            'sales_starts_at' => now()->addHour(),
            'sales_ends_at' => now()->addDay(),
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tickets.create'))
            ->assertOk()
            ->assertSee('Buat Event War Tiket');

        $this->get(route('admin.tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('Konser Test');
    }

    public function test_admin_dashboard_shows_active_users_popular_events_and_recent_orders(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $participant = User::factory()->create();
        $participant->forceFill(['last_seen_at' => now()])->save();
        $ticket = Ticket::query()->create([
            'name' => 'Konser Favorit',
            'price' => 500000,
            'stock' => 3,
            'sales_starts_at' => now()->subHour(),
            'sales_ends_at' => now()->addDay(),
            'is_published' => true,
        ]);
        $participant->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 2,
            'total' => 1000000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Peserta paling aktif')
            ->assertSee('Konser Favorit')
            ->assertSee($participant->name)
            ->assertSee('2 slot');
    }

    public function test_admin_cannot_delete_event_with_existing_orders(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $user = User::factory()->create();
        $ticket = Ticket::query()->create([
            'name' => 'Tiket Terjual',
            'price' => 500000,
            'stock' => 4,
        ]);
        $user->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 1,
            'total' => $ticket->price,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.tickets.destroy', $ticket));

        $response->assertSessionHasErrors('ticket');
        $this->assertModelExists($ticket);
    }
}
