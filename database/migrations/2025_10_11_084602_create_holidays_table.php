<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('holidays', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->date('start_date');
      $table->date('end_date'); // inklusif
      $table->boolean('is_active')->default(true);
      $table->foreignId('term_id')->constrained('academic_terms')->restrictOnDelete();
      $table->timestamps();
      
      $table->index(['term_id','start_date','end_date'], 'hol_term_range_idx');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('holidays');
  }
};
