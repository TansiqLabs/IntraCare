<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('patient_id', 26);
            $table->string('allergen');
            $table->string('severity', 20); // mild, moderate, severe, life_threatening
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};
