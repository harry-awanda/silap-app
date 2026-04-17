<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('jadwal_piket', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('guru_id');
      $table->string('hari_piket'); // Misalnya: 'Monday', 'Tuesday', dll.
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->foreign('guru_id')->references('id')->on('guru')->onDelete('cascade');
      // Menambahkan constraint unik
      $table->unique(['guru_id', 'hari_piket'], 'guru_piket_unique');      
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('jadwal_piket');
  }
};
