<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_auditable_model_creates_audit_log_with_created_at(): void
    {
        $user = User::factory()->create();

        $log = AuditLog::query()
            ->where('auditable_type', $user->getMorphClass())
            ->where('auditable_id', $user->getKey())
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log, 'Expected an audit log row for user creation.');
        $this->assertNotNull($log->created_at, 'Expected audit_logs.created_at to be set.');
    }
}
