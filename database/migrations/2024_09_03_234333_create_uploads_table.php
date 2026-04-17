<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('uploads', function (Blueprint $table) {
      $table->id();
      $table->string('file_name');
      $table->string('file_path');
      $table->string('description')->nullable();
      $table->string('file_type');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::dropIfExists('uploads');
  }
};
