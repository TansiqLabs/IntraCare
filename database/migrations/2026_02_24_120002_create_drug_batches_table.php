<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('drug_id', 26);
            $table->string('batch_number', 60);
            $table->date('expiry_date')->nullable();

            $table->unsignedInteger('quantity_received')->default(0);
            $table->unsignedInteger('quantity_on_hand')->default(0);

            $table->bigInteger('unit_cost')->default(0); // smallest currency unit
            $table->bigInteger('sale_price')->default(0); // smallest currency unit

            $table->string('supplier_name')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['drug_id', 'batch_number']);
            $table->index(['drug_id', 'expiry_date']);
            $table->index(['quantity_on_hand']);

            $table->foreign('drug_id')->references('id')->on('drugs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_batches');
    }
};
