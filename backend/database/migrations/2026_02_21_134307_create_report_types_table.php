<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_types', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // Supports {en: "Locust", ar: "جراد", fr: "Criquet"}
            $table->json('description')->nullable();
            $table->string('icon')->nullable(); // URL or icon name
            $table->string('color')->default('gray'); // e.g. red, yellow, green
            $table->integer('severity_level')->default(1); // 1-5 scale
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_types');
    }
};
