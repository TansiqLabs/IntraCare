<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QueueDepartment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class QueueDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_display_page_loads(): void
    {
        $dept = QueueDepartment::create([
            'name' => 'OPD',
            'code' => 'OPD',
            'floor' => 1,
            'is_active' => true,
        ]);

        $this->get('/queue/display/'.$dept->code)
            ->assertOk()
            ->assertSee('Queue Display');
    }
}
