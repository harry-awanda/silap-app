<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void {
    Schema::create('morning_activities', function (Blueprint $table) {
      $table->id();
      $table->string('kode', 20)->unique();   // upacara | senam | kerohanian
      $table->string('nama', 50);
      $table->tinyInteger('weekday')->nullable();         // 1=Senin, ... 7=Minggu (Carbon isoWeekday)
      $table->tinyInteger('sort_order')->default(0);
      $table->boolean('active')->default(true);
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('morning_activities');
  }
};