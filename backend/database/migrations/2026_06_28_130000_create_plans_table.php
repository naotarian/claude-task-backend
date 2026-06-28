<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // free | pro | business
            $table->string('name');
            $table->unsignedInteger('price_monthly')->default(0); // JPY
            // Limits. NULL means unlimited.
            $table->unsignedInteger('max_projects')->nullable();
            $table->unsignedInteger('max_members_per_project')->nullable();
            $table->unsignedBigInteger('max_storage_bytes_per_project')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
