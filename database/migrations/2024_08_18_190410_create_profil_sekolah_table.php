<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('profil_sekolah', function (Blueprint $table) {
      $table->id();
      $table->string('nama_sekolah');
      $table->string('npsn', 10);
      $table->string('email', 100)->nullable();
      $table->string('nomor_telepon', 15)->nullable();
      $table->text('alamat')->nullable();
      $table->foreignId('kepala_sekolah_id')->nullable()->constrained('guru')->onDelete('set null'); // Menambahkan foreign key kepala_sekolah_id
      $table->foreignId('kesiswaan_id')->nullable()->constrained('guru')->onDelete('set null'); // Menambahkan foreign key kesiswaan_id
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('profil_sekolah');
  }
};