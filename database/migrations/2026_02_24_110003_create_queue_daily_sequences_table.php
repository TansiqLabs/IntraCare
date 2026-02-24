<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_daily_sequences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('queue_department_id', 26);
            $table->date('token_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['queue_department_id', 'token_date']);

            $table->foreign('queue_department_id')
                ->references('id')
                ->on('queue_departments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_daily_sequences');
    }
};
