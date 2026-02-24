<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('drug_id', 26);
            $table->string('drug_batch_id', 26)->nullable();

            $table->string('type', 20); // receive, dispense, adjust, return
            $table->integer('quantity'); // signed: +receive, -dispense

            $table->string('reference_type')->nullable();
            $table->string('reference_id', 26)->nullable();

            $table->string('performed_by', 26)->nullable();
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['drug_id', 'occurred_at']);
            $table->index(['drug_batch_id']);
            $table->index(['type']);

            $table->foreign('drug_id')->references('id')->on('drugs')->restrictOnDelete();
            $table->foreign('drug_batch_id')->references('id')->on('drug_batches')->nullOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
