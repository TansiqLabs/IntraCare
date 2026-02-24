<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('lab_order_test_id', 26);
            $table->string('lab_test_parameter_id', 26);
            $table->string('value')->nullable(); // the entered result value
            $table->boolean('is_abnormal')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['lab_order_test_id', 'lab_test_parameter_id'], 'lab_results_test_param_unique');

            $table->foreign('lab_order_test_id')->references('id')->on('lab_order_tests')->restrictOnDelete();
            $table->foreign('lab_test_parameter_id')->references('id')->on('lab_test_parameters')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
