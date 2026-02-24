<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('patient_id', 26);
            $table->string('visit_id', 26)->nullable();
            $table->string('doctor_id', 26);
            $table->string('order_number', 30)->unique(); // LO-20260224-0001
            $table->string('status', 20)->default('pending_payment');
            $table->string('priority', 10)->default('routine'); // routine, urgent, stat
            $table->text('clinical_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');

            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
    }
};
