<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('siswa', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
      $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
      $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

      $table->string('nis', 11)->unique();
      $table->string('nama_lengkap', 50);
      $table->string('jenis_kelamin')->nullable();
      $table->string('tempat_lahir')->nullable();
      $table->date('tanggal_lahir')->nullable();
      $table->string('agama')->nullable();
      $table->text('alamat')->nullable();
      $table->string('kontak')->nullable();
      $table->string('photo')->nullable();
      
      $table->string('nama_ayah', 50)->nullable();
      $table->string('pekerjaan_ayah', 50)->nullable();
      $table->string('kontak_ayah', 20)->nullable();
      
      $table->string('nama_ibu', 50)->nullable();
      $table->string('pekerjaan_ibu', 50)->nullable();
      $table->string('kontak_ibu', 20)->nullable();
      
      $table->string('nama_wali_murid', 50)->nullable();
      $table->string('kontak_wali', 20)->nullable();
      
      $table->string('alamat_orangtua')->nullable();
      $table->string('alamat_wali')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('siswa');
  }
};
