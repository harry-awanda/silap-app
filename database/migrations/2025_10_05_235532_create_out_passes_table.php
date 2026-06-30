<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('out_passes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      // Kelas & guru piket
      $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
      $table->foreignId('picket_teacher_id')->constrained('guru')->restrictOnDelete();

      // Tujuan dan alasan (pakai string, bukan enum)
      $table->string('destination', 255);
      $table->string('reason', 100)->default('lainnya');
      // misalnya isi: 'membeli_barang', 'sakit_pulang', 'kegiatan_luar', 'lainnya'

      // Persetujuan wali kelas
      $table->foreignId('approved_by_id')->nullable()->constrained('guru')->nullOnDelete();
      $table->string('approved_by_name', 120)->nullable();
      $table->string('approval_method', 50)->nullable(); // telepon, whatsapp, lisan, dll
      $table->dateTime('approval_at')->nullable();

      // Waktu umum & flag
      $table->dateTime('time_out');
      $table->boolean('return_expected')->default(true); // false untuk sakit pulang
      $table->text('notes')->nullable();

      // Audit
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

      $table->timestamps();

      // Indeks bantu
      $table->index(['classroom_id', 'time_out']);
      $table->index(['picket_teacher_id']);
      $table->index(['reason']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('out_passes');
  }
};
