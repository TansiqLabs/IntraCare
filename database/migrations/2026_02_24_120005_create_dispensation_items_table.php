<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensation_items', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('dispensation_id', 26);
            $table->string('drug_id', 26);
            $table->string('drug_batch_id', 26)->nullable();

            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price')->default(0);
            $table->bigInteger('line_total')->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['dispensation_id']);

            $table->foreign('dispensation_id')->references('id')->on('dispensations')->restrictOnDelete();
            $table->foreign('drug_id')->references('id')->on('drugs')->restrictOnDelete();
            $table->foreign('drug_batch_id')->references('id')->on('drug_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispensation_items');
    }
};
