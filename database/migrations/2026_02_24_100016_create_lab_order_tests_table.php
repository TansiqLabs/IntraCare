<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_order_tests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('lab_order_id', 26);
            $table->string('lab_test_id', 26);
            $table->string('status', 20)->default('pending'); // pending, sample_collected, processing, result_entered, verified, rejected
            $table->string('verified_by', 26)->nullable(); // pathologist
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('status');

            $table->foreign('lab_order_id')->references('id')->on('lab_orders')->restrictOnDelete();
            $table->foreign('lab_test_id')->references('id')->on('lab_test_catalog')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_tests');
    }
};
