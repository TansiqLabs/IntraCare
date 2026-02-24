<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensations', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('patient_id', 26)->nullable();
            $table->string('prescription_id', 26)->nullable();

            $table->string('status', 20)->default('draft'); // draft, completed, cancelled
            $table->string('dispensed_by', 26)->nullable();
            $table->timestamp('dispensed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'dispensed_at']);
            $table->index(['patient_id']);

            $table->foreign('patient_id')->references('id')->on('patients')->nullOnDelete();
            $table->foreign('prescription_id')->references('id')->on('prescriptions')->nullOnDelete();
            $table->foreign('dispensed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensations');
    }
};
