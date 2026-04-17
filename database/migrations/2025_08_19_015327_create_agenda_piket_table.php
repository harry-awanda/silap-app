<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('agenda_piket', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->date('tanggal'); // Tanggal agenda piket
      $table->text('kejadian_normal')->nullable(); // Catatan kejadian normal
      $table->text('kejadian_masalah')->nullable(); // Catatan kejadian masalah
      $table->text('solusi')->nullable(); // Solusi untuk kejadian masalah
      $table->json('guru_piket')->nullable(); // Menyimpan dua guru piket sebagai JSON
      $table->json('absensi_per_kelas')->nullable(); // Absensi siswa per kelas (disimpan dalam format JSON)
      $table->json('absensi_per_tingkat')->nullable(); // Absensi siswa per tingkat (disimpan dalam format JSON)
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('agenda_piket');
  }
};