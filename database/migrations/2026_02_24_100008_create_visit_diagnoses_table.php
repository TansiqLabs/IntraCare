<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_diagnoses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('visit_id', 26);
            $table->string('icd_code_id', 26);
            $table->string('diagnosis_type', 20); // primary, secondary, differential
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('visit_id')->references('id')->on('visits')->restrictOnDelete();
            $table->foreign('icd_code_id')->references('id')->on('icd_codes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_diagnoses');
    }
};
