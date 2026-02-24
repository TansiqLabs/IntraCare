<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('mr_number', 20)->unique(); // MR-000001
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10); // male, female, other
            $table->string('blood_group', 5)->nullable(); // A+, B-, etc.
            $table->string('cnic', 20)->nullable(); // National ID
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('registered_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone');
            $table->index('cnic');
            $table->index('registered_by');

            $table->foreign('registered_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
