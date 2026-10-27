<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_or_another_admin_account(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Pengguna & peran', false)
            ->assertSee('Tambah pengguna/admin');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin-baru@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => 'admin',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'admin-baru@example.test',
            'role' => 'admin',
            'is_admin' => true,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => User::query()->where('email', 'admin-baru@example.test')->value('id'),
            'balance' => 0,
        ]);
    }

    public function test_deleting_an_account_with_order_history_deactivates_it_and_preserves_the_order(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'is_admin' => true])->save();
        $user = User::factory()->create();
        $ticket = Ticket::query()->create([
            'name' => 'Konser Riwayat',
            'price' => 500000,
            'stock' => 4,
            'sales_starts_at' => now()->subHour(),
            'sales_ends_at' => now()->addDay(),
            'is_published' => true,
        ]);
        $order = $user->orders()->create([
            'ticket_id' => $ticket->id,
            'ticket_name' => $ticket->name,
            'unit_price' => $ticket->price,
            'quantity' => 1,
            'total' => $ticket->price,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
        $this->assertModelExists($order);
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $user->forceFill(['is_active' => false])->save();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_their_own_account_and_can_disable_another_admin(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'is_admin' => true])->save();

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin))
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);

        $anotherAdmin = User::factory()->create();
        $anotherAdmin->forceFill(['role' => 'admin', 'is_admin' => true])->save();
        $this->assertSame(2, User::query()->where('role', 'admin')->where('is_active', true)->count());
        $this->actingAs($admin)
            ->patch(route('admin.users.status', $anotherAdmin))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id, 'is_active' => false]);
    }
}
