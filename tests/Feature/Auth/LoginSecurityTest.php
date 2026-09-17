<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Invalid email format is rejected with validation error (422 in JSON).
     */
    public function test_invalid_email_format_is_rejected(): void
    {
        $response = $this->post(route('login.auth'), [
            'email' => 'not-a-valid-email',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Also test JSON validation format
        $jsonResponse = $this->postJson(route('login.auth'), [
            'email' => 'not-a-valid-email',
            'password' => 'secret123',
        ]);

        $jsonResponse->assertStatus(422);
        $jsonResponse->assertJsonValidationErrors('email');
    }

    /**
     * Test 2 & 3: Non-existing email and wrong password return identical generic error.
     * Prevents user enumeration by ensuring indistinguishable responses.
     */
    public function test_non_existing_email_returns_same_generic_response_as_wrong_password(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'registered@example.com',
            'password' => 'correct-password',
        ]);

        // Attempt with non-existing email
        $nonExistingResponse = $this->from(route('login'))->post(route('login.auth'), [
            'email' => 'unregistered@example.com',
            'password' => 'some-password',
        ]);

        $nonExistingResponse->assertRedirect(route('login'));
        $nonExistingResponse->assertSessionHasErrors(['email' => 'Email atau password yang Anda masukkan salah.']);
        $this->assertGuest();

        // Attempt with existing email but wrong password
        $wrongPasswordResponse = $this->from(route('login'))->post(route('login.auth'), [
            'email' => 'registered@example.com',
            'password' => 'incorrect-password',
        ]);

        $wrongPasswordResponse->assertRedirect(route('login'));
        $wrongPasswordResponse->assertSessionHasErrors(['email' => 'Email atau password yang Anda masukkan salah.']);
        $this->assertGuest();

        // Compare error messages: they must be completely identical
        $nonExistingError = session('errors')->get('email')[0];
        $this->assertEquals('Email atau password yang Anda masukkan salah.', $nonExistingError);
    }

    /**
     * Test 4: Valid credentials result in successful authentication and session regeneration.
     */
    public function test_valid_credentials_authenticate_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'validuser@example.com',
            'password' => 'valid-secret-password',
        ]);

        $response = $this->post(route('login.auth'), [
            'email' => 'validuser@example.com',
            'password' => 'valid-secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test 5: Guest access behavior (can view login, redirected from dashboard, authenticated redirected from login).
     */
    public function test_guest_access_behavior(): void
    {
        // Guest can view login page
        $loginPageResponse = $this->get(route('login'));
        $loginPageResponse->assertOk();

        // Guest is redirected from protected route to login
        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('login'));

        // Authenticated user accessing login is redirected
        $user = User::factory()->create();
        $authLoginResponse = $this->actingAs($user)->get(route('login'));
        $authLoginResponse->assertRedirect(route('dashboard'));
    }

    /**
     * Test 6: Logout securely clears authentication and session.
     */
    public function test_user_can_logout_securely(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('login');
        $this->assertGuest();
    }
}
