<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('attendance_face_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('term_id')
        ->constrained('academic_terms')
        ->cascadeOnDelete();

      $table->foreignId('siswa_id')
        ->constrained('siswa')
        ->cascadeOnDelete();

      $table->foreignId('classroom_id')
        ->constrained('classrooms')
        ->cascadeOnDelete();

      $table->date('date');
      $table->time('time')->nullable();

      // attempt result (pass/fail) + reason ringkas
      $table->enum('result', ['pass', 'fail'])->index();
      $table->string('reason', 191)->nullable();

      // selaras dengan attendances
      $table->decimal('similarity', 5, 3)->nullable();
      $table->boolean('liveness_passed')->default(false);
      $table->decimal('liveness_score', 5, 3)->nullable(); // opsional (kalau nanti ada skor)

      // audit device (bukan IP)
      $table->string('device_id', 191)->nullable();
      $table->string('user_agent', 255)->nullable();

      // snapshot geofence (penting untuk attempt yang gagal, attendances hanya sukses)
      $table->decimal('latitude', 10, 7)->nullable();
      $table->decimal('longitude', 10, 7)->nullable();
      $table->unsignedInteger('accuracy_m')->nullable();

      $table->timestamps();

      $table->index(['term_id', 'classroom_id', 'date'], 'att_face_logs_term_class_date_idx');
      $table->index(['term_id', 'siswa_id', 'date'], 'att_face_logs_term_siswa_date_idx');
    });
  }

  public function down(): void {
    Schema::dropIfExists('attendance_face_logs');
  }
};
