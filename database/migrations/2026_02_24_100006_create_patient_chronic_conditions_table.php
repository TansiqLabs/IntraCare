<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_chronic_conditions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('patient_id', 26);
            $table->string('condition_name');
            $table->string('icd_code_id', 26)->nullable();
            $table->date('diagnosed_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('icd_code_id')->references('id')->on('icd_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_chronic_conditions');
    }
};
