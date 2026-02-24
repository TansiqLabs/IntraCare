<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('queue_department_id', 26);
            $table->string('queue_counter_id', 26)->nullable();

            $table->string('patient_id', 26)->nullable();
            $table->string('visit_id', 26)->nullable();

            $table->date('token_date');
            $table->unsignedInteger('token_number');
            $table->string('token_display', 20); // e.g. OPD-012

            $table->string('status', 20)->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('no_show_at')->nullable();

            $table->string('created_by', 26)->nullable();
            $table->timestamps();

            $table->unique(['queue_department_id', 'token_date', 'token_number']);
            $table->index(['queue_department_id', 'token_date', 'status']);
            $table->index(['queue_counter_id', 'status']);

            $table->foreign('queue_department_id')
                ->references('id')
                ->on('queue_departments')
                ->restrictOnDelete();

            $table->foreign('queue_counter_id')
                ->references('id')
                ->on('queue_counters')
                ->nullOnDelete();

            $table->foreign('patient_id')
                ->references('id')
                ->on('patients')
                ->nullOnDelete();

            $table->foreign('visit_id')
                ->references('id')
                ->on('visits')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
