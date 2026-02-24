<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('prescription_id', 26);
            $table->string('drug_name');
            $table->string('dosage', 100)->nullable();
            $table->string('frequency', 100)->nullable(); // e.g. "1-0-1", "TID"
            $table->string('duration', 100)->nullable(); // e.g. "7 days", "2 weeks"
            $table->string('route', 50)->nullable(); // oral, IV, IM, topical
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
