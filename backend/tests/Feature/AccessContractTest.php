<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_data_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_registration_is_not_public(): void
    {
        $this->postJson('/api/register', [])->assertNotFound();
    }

    public function test_login_establishes_session_and_logout_removes_it(): void
    {
        $user = User::factory()->create(['password' => 'Only-for-tests-123!']);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'Only-for-tests-123!'])
            ->assertOk()->assertJsonPath('user.email', $user->email);
        $this->getJson('/api/me')->assertOk();
        $this->postJson('/api/logout')->assertNoContent();
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertUnprocessable();
    }
}
