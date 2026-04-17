<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
  * Run the migrations.
  */
  public function up(): void {
    Schema::create('guru', function (Blueprint $table) {
      $table->id();
      $table->string('nip'); //nip: NIP || NIPPPK || NRPTK || NRHS
      $table->string('nama_lengkap');
      $table->string('tempat_lahir')->nullable();
      $table->date('tanggal_lahir')->nullable();
      $table->string('jenis_kelamin')->nullable();
      $table->string('kontak')->nullable();
      $table->text('alamat')->nullable();
      $table->string('photo')->nullable();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->timestamps();
    });
  }
  
  /**
  * Reverse the migrations.
  */
  public function down(): void {
    Schema::dropIfExists('guru');
  }
};
