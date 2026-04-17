<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('academic_terms', function (Blueprint $table) {
      $table->id();
      $table->string('name'); // Contoh: "TP 2025/2026 – Ganjil"
      $table->unsignedSmallInteger('year_start');
      $table->unsignedSmallInteger('year_end');
      $table->enum('semester', ['ganjil','genap']);
      $table->date('start_date')->nullable();
      $table->date('end_date')->nullable();
      $table->boolean('is_active')->default(false)->index();
      $table->timestamp('lock_attendance_at')->nullable();
      $table->timestamp('lock_violation_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('academic_terms');
  }
};
