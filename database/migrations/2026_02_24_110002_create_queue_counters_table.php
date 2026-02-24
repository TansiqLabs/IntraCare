<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_counters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('queue_department_id', 26);
            $table->string('name'); // e.g. Counter 1
            $table->string('code', 20)->unique(); // e.g. OPD-1
            $table->unsignedSmallInteger('floor')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['queue_department_id', 'is_active']);

            $table->foreign('queue_department_id')
                ->references('id')
                ->on('queue_departments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_counters');
    }
};
