<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_samples', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('lab_order_id', 26);
            $table->string('lab_order_test_id', 26);
            $table->string('barcode', 50)->unique(); // printed on label
            $table->string('collected_by', 26)->nullable(); // nurse/technician
            $table->timestamp('collected_at')->nullable();
            $table->string('status', 20)->default('collected'); // collected, received_in_lab, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('status');

            $table->foreign('lab_order_id')->references('id')->on('lab_orders')->cascadeOnDelete();
            $table->foreign('lab_order_test_id')->references('id')->on('lab_order_tests')->cascadeOnDelete();
            $table->foreign('collected_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_samples');
    }
};
