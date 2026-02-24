<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drugs', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('generic_name');
            $table->string('brand_name')->nullable();
            $table->string('formulation', 50)->nullable(); // tablet, capsule, syrup, injection
            $table->string('strength', 50)->nullable(); // 500mg, 5mg/5ml
            $table->string('unit', 30)->default('pcs'); // pcs, ml, vial, etc.

            $table->string('barcode', 100)->nullable()->unique();
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('reorder_level')->default(0); // low-stock threshold
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['is_active']);
            $table->index(['generic_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
