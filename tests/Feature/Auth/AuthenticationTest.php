<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_their_username(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'usuario' => $user->usuario,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'usuario' => $user->usuario,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = User::factory()->inactivo()->create();

        $this->post('/login', [
            'usuario' => $user->usuario,
            'password' => 'password',
        ])->assertSessionHasErrors('usuario');

        $this->assertGuest();
    }

    public function test_a_user_deactivated_while_logged_in_is_logged_out(): void
    {
        $user = User::factory()->inactivo()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guards_are_sent_to_the_scanner_after_login(): void
    {
        $guarda = User::factory()->guarda()->create();

        $this->actingAs($guarda)->get('/dashboard')->assertRedirect(route('escaner.index'));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
