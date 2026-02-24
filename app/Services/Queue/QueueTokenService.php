<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Enums\QueueTicketStatus;
use App\Models\QueueDailySequence;
use App\Models\QueueDepartment;
use App\Models\QueueTicket;
use Illuminate\Support\Facades\DB;

final class QueueTokenService
{
    /**
     * Issue a new queue ticket for a department. Token numbers reset daily per department.
     */
    public function issueTicket(
        QueueDepartment $department,
        ?string $patientId = null,
        ?string $visitId = null,
        ?string $createdBy = null,
        ?\DateTimeInterface $tokenDate = null,
    ): QueueTicket {
        $date = $tokenDate ? \Carbon\Carbon::instance($tokenDate)->toDateString() : now()->toDateString();

        return DB::transaction(function () use ($department, $patientId, $visitId, $createdBy, $date) {
            // Row-level lock on the sequence record so concurrent requests remain sequential.
            $seq = QueueDailySequence::query()
                ->where('queue_department_id', $department->getKey())
                ->whereDate('token_date', $date)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $seq = QueueDailySequence::create([
                    'queue_department_id' => $department->getKey(),
                    'token_date' => $date,
                    'last_number' => 0,
                ]);

                // Lock the just-created row for the rest of the transaction.
                $seq->refresh();
                QueueDailySequence::query()->whereKey($seq->getKey())->lockForUpdate()->first();
            }

            $seq->last_number = (int) $seq->last_number + 1;
            $seq->save();

            $display = sprintf('%s-%03d', strtoupper($department->code), $seq->last_number);

            return QueueTicket::create([
                'queue_department_id' => $department->getKey(),
                'patient_id' => $patientId,
                'visit_id' => $visitId,
                'token_date' => $date,
                'token_number' => $seq->last_number,
                'token_display' => $display,
                'status' => QueueTicketStatus::Waiting,
                'created_by' => $createdBy,
            ]);
        }, 3);
    }
}
