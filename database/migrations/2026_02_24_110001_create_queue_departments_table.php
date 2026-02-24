<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('code', 20)->unique(); // e.g. OPD, LAB, PHARM, ACC
            $table->unsignedSmallInteger('floor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'floor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_departments');
    }
};
