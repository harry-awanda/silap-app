<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('attendances', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
      $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
      
      $table->enum('status', ['hadir','terlambat','sakit','izin','alpa'])->default('hadir');
      $table->string('source')->default('self'); // asal input

      // untuk menandai record dibuat otomatis (auto alpa)
      $table->boolean('auto_marked')->default(false);

      $table->string('notes')->nullable();

      $table->date('date'); // tanggal presensi (wajib; dipakai unique bersama siswa_id)
      $table->time('time'); // waktu presensi (wajib; auto alpa isi via config default_time)
      
      $table->decimal('latitude', 10, 7)->nullable();
      $table->decimal('longitude', 10, 7)->nullable();
      $table->unsignedInteger('accuracy_m')->nullable();
      
      $table->string('user_agent')->nullable();
      $table->timestamps();
      
      $table->unique(['term_id','siswa_id','date'], 'att_term_siswa_date_unique');

      // Index bantu yang memasukkan term_id
      $table->index(['term_id','status'], 'att_idx_term_status');
      $table->index(['term_id','classroom_id','date'], 'att_idx_term_classroom_date');
      $table->index(['term_id','source'], 'att_idx_term_source');
      $table->index(['term_id','auto_marked'], 'att_idx_term_auto_marked');
      // Untuk memudahkan audit asal data & auto flag
    });
  }

  public function down(): void {
    Schema::dropIfExists('attendances');
  }
};
