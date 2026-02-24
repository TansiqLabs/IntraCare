<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('patient_id', 26);
            $table->string('name');
            $table->string('relation', 50)->nullable();
            $table->string('phone', 20);
            $table->boolean('is_emergency')->default(false);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_contacts');
    }
};
