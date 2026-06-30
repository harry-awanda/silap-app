<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('face_profiles', function (Blueprint $table) {
      $table->id();
      $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();

      // hanya satu yang aktif per siswa (kita jaga via aplikasi / opsional index tambahan)
      $table->boolean('is_active')->default(true);

      // user yang melakukan enrollment/reset (admin/guru), boleh null untuk sistem
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['siswa_id', 'is_active'], 'face_profiles_siswa_active_idx');
    });
  }

  public function down(): void {
    Schema::dropIfExists('face_profiles');
  }
};