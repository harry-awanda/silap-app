<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('pelanggaran_siswa', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreignId('siswa_id')->constrained('siswa')->onDelete('cascade');
      $table->date('tanggal_pelanggaran');
      $table->string('status')->nullable(); // null | diproses | selesai
      $table->string('tindakan')->nullable(); // merujuk pada pembinaan oleh wali kelas / guru_bk
      $table->text('catatan_waliKelas')->nullable();
      $table->text('catatan_kesiswaan')->nullable();
      $table->text('catatan_guruBK')->nullable();
      $table->text('keterangan')->nullable();
      $table->timestamps();

      $table->index(['term_id','siswa_id','tanggal_pelanggaran'], 'idx_term_siswa_tanggal');
    });
  }

  public function down(): void {
    Schema::dropIfExists('pelanggaran_siswa');
  }
};