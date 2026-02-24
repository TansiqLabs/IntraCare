<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('invoice_number', 30)->unique(); // INV-20260224-0001
            $table->string('patient_id', 26);
            $table->string('visit_id', 26)->nullable();
            $table->string('invoice_type', 20); // lab, opd, pharmacy
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('total')->default(0);
            $table->bigInteger('paid')->default(0);
            $table->bigInteger('balance')->default(0);
            $table->string('status', 20)->default('draft'); // draft, issued, paid, partial, cancelled, refunded
            $table->string('created_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('invoice_type');

            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('visit_id')->references('id')->on('visits')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
