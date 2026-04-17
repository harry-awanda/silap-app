<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('classrooms', function (Blueprint $table) {
      $table->id();
      
      // Per-term (scope via trait)
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      
      $table->string('nama_kelas',100);      // contoh: XI RPL 1
      $table->tinyInteger('tingkat');    // 10/11/12 (atau 1/2/3 sesuai kebutuhan)
      
      $table->timestamps();
      
      // Unik per term agar "XI RPL 1" bisa diulang di term lain
      $table->unique(['term_id','nama_kelas'], 'classrooms_term_nama_unique');
      $table->index(['term_id','tingkat'], 'classrooms_term_tingkat_idx');
    });
  }

  public function down(): void {
    Schema::dropIfExists('classrooms');
  }
};