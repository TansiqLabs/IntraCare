<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\QueueDepartment;
use App\Services\Queue\QueueTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class QueueTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issues_sequential_tokens_per_department_per_day(): void
    {
        $dept = QueueDepartment::create([
            'name' => 'OPD',
            'code' => 'OPD',
            'floor' => 1,
            'is_active' => true,
        ]);

        $svc = new QueueTokenService();

        $t1 = $svc->issueTicket($dept);
        $t2 = $svc->issueTicket($dept);

        $this->assertSame(1, $t1->token_number);
        $this->assertSame('OPD-001', $t1->token_display);
        $this->assertSame(2, $t2->token_number);
        $this->assertSame('OPD-002', $t2->token_display);
    }

    public function test_tokens_reset_for_new_day(): void
    {
        $dept = QueueDepartment::create([
            'name' => 'Lab',
            'code' => 'LAB',
            'floor' => 2,
            'is_active' => true,
        ]);

        $svc = new QueueTokenService();

        $day1 = new \DateTimeImmutable('2026-02-24');
        $day2 = new \DateTimeImmutable('2026-02-25');

        $t1 = $svc->issueTicket($dept, tokenDate: $day1);
        $t2 = $svc->issueTicket($dept, tokenDate: $day2);

        $this->assertSame(1, $t1->token_number);
        $this->assertSame(1, $t2->token_number);
        $this->assertSame('LAB-001', $t2->token_display);
    }
}
