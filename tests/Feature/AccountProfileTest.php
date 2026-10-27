<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_update_their_profile_information_and_email_preference(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.profile.edit'))
            ->assertOk()
            ->assertSee('Informasi pengguna')
            ->assertSee('id="nama_lengkap" name="name"', false)
            ->assertSee('id="email" name="email"', false)
            ->assertSee('id="nomor_telepon" name="phone"', false);

        $response = $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'nama-baru@example.test',
            'phone' => '+628123456789',
            'email_notifications' => '1',
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => 'nama-baru@example.test',
            'phone' => '+628123456789',
            'email_notifications' => true,
        ]);
    }
}
