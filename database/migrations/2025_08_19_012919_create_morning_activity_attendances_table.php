<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  
  public function up(): void {
    Schema::create('morning_activity_attendances', function (Blueprint $table) {
      $table->id();
      
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
      $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
      $table->foreignId('morning_activity_id')->constrained('morning_activities')->cascadeOnDelete();
      $table->date('tanggal');
      $table->string('custom_activity_name', 100)->nullable();  
      $table->enum('status', ['hadir','tidak_hadir'])->default('hadir');
      $table->string('keterangan', 100)->nullable(); // opsional
      $table->timestamps();

      $table->unique(['term_id','siswa_id','tanggal'], 'uniq_term_siswa_tanggal');

      $table->index(['term_id','classroom_id','tanggal','morning_activity_id'], 'idx_term_class_date_activity');
    });
  }

  public function down(): void {
    Schema::dropIfExists('morning_activity_attendances');
  }
};