<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // When not installed (no users), any non-setup route should redirect to setup.
        $this->get('/')->assertRedirect('/setup');

        // After a user exists, the root route should redirect to the admin panel.
        User::factory()->create();
        cache()->forget('intracare.installed');

        $this->get('/')->assertRedirect('/admin');
    }
}
