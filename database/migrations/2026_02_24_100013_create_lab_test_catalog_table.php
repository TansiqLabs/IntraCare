<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_catalog', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('department_id', 26);
            $table->string('sample_type_id', 26)->nullable();
            $table->string('code', 20)->unique(); // CBC, LFT, RFT, etc.
            $table->string('name');
            $table->string('short_name', 50)->nullable();
            $table->bigInteger('cost')->default(0); // in smallest currency unit (paisa)
            $table->integer('turn_around_minutes')->nullable(); // expected TAT
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('instructions')->nullable(); // fasting required, etc.
            $table->timestamps();

            $table->index('is_active');

            $table->foreign('department_id')->references('id')->on('lab_departments')->restrictOnDelete();
            $table->foreign('sample_type_id')->references('id')->on('lab_sample_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_catalog');
    }
};
