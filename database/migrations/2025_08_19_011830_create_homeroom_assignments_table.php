<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void {
    Schema::create('homeroom_assignments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
      $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();

      $table->timestamp('assigned_at')->nullable();
      $table->timestamp('started_at')->nullable()->index();
      $table->timestamp('ended_at')->nullable()->index();
      $table->timestamps();
      
      // Slot unik saat aktif: bernilai classroom_id jika aktif, null jika non-aktif
      $table->unsignedBigInteger('active_class_slot')
      ->storedAs('IF(ended_at IS NULL, classroom_id, NULL)')
      ->nullable();
      $table->unsignedBigInteger('active_guru_slot')
      ->storedAs('IF(ended_at IS NULL, guru_id, NULL)')
      ->nullable();

      // Unik: hanya berlaku untuk baris aktif (non-null). Riwayat (null) boleh banyak.
      $table->unique(['term_id','active_class_slot'], 'uniq_active_class_per_term');
      $table->unique(['term_id','active_guru_slot'], 'uniq_active_guru_per_term');

      $table->index(['term_id','classroom_id']);
      $table->index(['term_id','guru_id']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('homeroom_assignments');
  }
};
