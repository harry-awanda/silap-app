<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('out_pass_students', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreignId('out_pass_id')->constrained('out_passes')->cascadeOnDelete();
      $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();

      // Jam keluar per siswa (boleh null → fallback ke header.time_out di layer aplikasi)
      $table->dateTime('time_out')->nullable();
      $table->dateTime('time_back')->nullable();

      // Status per siswa
      $table->enum('status', ['approved_out', 'returned', 'not_returning', 'canceled'])->default('approved_out');

      // Petugas yang menangani saat kembali (bisa piket shift berbeda)
      $table->foreignId('handled_by_id')->nullable()->constrained('guru')->nullOnDelete();

      // Catatan singkat
      $table->string('remarks', 255)->nullable();

      // ====== Guard unik “aktif keluar” (hanya untuk baris dengan status aktif) ======
      // MySQL tidak mendukung partial index, jadi pakai generated column + unique.
      // active_guard bernilai siswa_id hanya jika status aktif (approved_out) dan belum time_back serta return_expected di header = true.
      // Catatan: karena generated column tidak bisa merujuk kolom tabel lain secara langsung (header.return_expected),
      // maka kita hanya proteksi di level aplikasi + index bantu untuk query cepat.
      $table->unsignedBigInteger('active_guard')->nullable(); // akan diisi di aplikasi saat create/update

      $table->timestamps();

      // Indeks & optimasi query
      $table->index(['siswa_id', 'status']);
      $table->index(['time_back']);
      $table->index(['out_pass_id', 'status']);

      // Mencegah multi-aktif per siswa:
      // UNIQUE pada active_guard memperbolehkan banyak NULL (baris non-aktif),
      // tetapi menolak ada >1 baris dengan active_guard = siswa_id yang sama.
      $table->unique(['active_guard']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('out_pass_students');
  }
};
