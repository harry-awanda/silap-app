<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  public function up(): void {
    Schema::create('face_embeddings', function (Blueprint $table) {
      $table->id();
      $table->foreignId('face_profile_id')
        ->constrained('face_profiles')
        ->cascadeOnDelete();

      // buat kolom dulu pakai tipe yang Laravel support
      $table->binary('embedding'); // placeholder

      $table->string('model_version', 50)->default('v1');
      $table->json('meta')->nullable();
      $table->timestamps();

      $table->index(['face_profile_id', 'model_version'], 'face_embeddings_profile_model_idx');
    });

    // ✅ ALTER harus di luar closure, setelah table benar-benar dibuat
    DB::statement("ALTER TABLE face_embeddings MODIFY embedding MEDIUMBLOB NOT NULL");
    // kalau Anda mau LONGBLOB:
    // DB::statement("ALTER TABLE face_embeddings MODIFY embedding LONGBLOB NOT NULL");
  }

  public function down(): void {
    Schema::dropIfExists('face_embeddings');
  }
};
