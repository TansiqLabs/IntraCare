<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('patient_id', 26);
            $table->string('doctor_id', 26);
            $table->string('visit_number', 30)->unique(); // V-20260224-0001
            $table->string('visit_type', 20); // opd, follow_up, emergency
            $table->string('status', 20)->default('waiting'); // waiting, in_progress, completed, cancelled
            $table->text('chief_complaint')->nullable();
            $table->text('examination_notes')->nullable();
            $table->text('plan')->nullable();
                $table->json('vitals')->nullable(); // {bp, temp, pulse, spo2, weight, height, bmi}
            $table->timestamp('visited_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('visited_at');

            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
