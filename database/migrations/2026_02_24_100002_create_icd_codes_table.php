<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('icd_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('short_description');
            $table->text('long_description')->nullable();
            $table->string('version', 10); // icd10, icd11
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('version');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icd_codes');
    }
};
