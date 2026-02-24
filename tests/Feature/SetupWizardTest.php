<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SetupWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_page_loads_when_no_users_exist(): void
    {
        $this->get('/setup')->assertOk();
    }

    public function test_setup_page_redirects_when_installed(): void
    {
        User::factory()->create();

        $this->get('/setup')->assertRedirect('/admin');
    }

    public function test_setup_post_creates_admin_and_redirects_to_admin(): void
    {
        $token = 'test-csrf-token';

        $response = $this
            ->withSession(['_token' => $token])
            ->post('/setup', [
            '_token' => $token,
            'hospital_name' => 'IntraCare HMS',
            'name' => 'System Administrator',
            'email' => 'admin@example.test',
            'password' => 'Lagbenah!33',
            'password_confirmation' => 'Lagbenah!33',
        ]);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
            'employee_id' => 'EMP-0001',
        ]);
    }
}
