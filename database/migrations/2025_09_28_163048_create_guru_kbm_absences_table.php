<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('guru_kbm_absences', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreignId('agenda_piket_id')->constrained('agenda_piket')->cascadeOnDelete();
      $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
      $table->enum('status', ['sakit','izin','alpa']);
      $table->string('keterangan')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('guru_kbm_absences');
  }
};
