<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('pelanggaran_siswa_pelanggaran', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreignId('pelanggaran_siswa_id')->constrained('pelanggaran_siswa')->cascadeOnDelete();
      $table->foreignId('pelanggaran_id')->constrained('pelanggaran')->cascadeOnDelete();
      $table->timestamps();
      
      $table->unique(['term_id', 'pelanggaran_siswa_id', 'pelanggaran_id'],'uniq_term_ps_pelanggaran');
      $table->index(['term_id', 'pelanggaran_siswa_id'], 'idx_term_ps');
    });
  }

  public function down(): void {
    Schema::dropIfExists('pelanggaran_siswa_pelanggaran');
  }
};