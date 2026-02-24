<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_parameters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('lab_test_id', 26);
            $table->string('name');
            $table->string('unit', 50)->nullable(); // mg/dL, g/L, cells/uL, etc.
            $table->string('normal_range_male')->nullable(); // "4.5-5.5" or "< 200"
            $table->string('normal_range_female')->nullable();
            $table->string('normal_range_child')->nullable();
            $table->string('method')->nullable(); // methodology used
            $table->string('field_type', 20)->default('numeric'); // numeric, text, select, boolean
                $table->json('field_options')->nullable(); // for select: ["Positive","Negative"]
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('lab_test_id')->references('id')->on('lab_test_catalog')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_parameters');
    }
};
