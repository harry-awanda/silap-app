<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('term_classroom_siswa', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms');
      $table->foreignId('classroom_id')->constrained('classrooms');
      $table->foreignId('siswa_id')->constrained('siswa');
      $table->string('status')->default('active');
      $table->timestamps();

      $table->unique(['term_id', 'siswa_id']);
      $table->index(['term_id', 'classroom_id']);
      // aktifkan kode dibawah untuk migrate fresh berikutnya
      // $table->index(['term_id', 'classroom_id', 'siswa_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('term_classroom_siswa');
  }
};
