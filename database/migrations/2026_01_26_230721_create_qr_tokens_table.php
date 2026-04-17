<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('qr_tokens', function (Blueprint $table) {
      $table->id();

      $table->string('token', 80)->unique(); // token QR
      $table->string('purpose', 30)->default('asse_borrower'); // future-proof

      // borrower type: student|teacher
      $table->string('subject_type', 20);
      $table->string('subject_ref', 30); // nis / nip (string biar fleksibel)

      // payload tambahan (optional)
      $table->json('payload')->nullable();

      $table->dateTime('expires_at')->index();
      $table->dateTime('used_at')->nullable()->index();
      $table->unsignedBigInteger('used_count')->default(0); // kalau mau multi use, default 0

      // audit optional
      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

      $table->timestamps();

      $table->index(['purpose', 'subject_type', 'subject_ref'], 'qr_tokens_subject_idx');
    });
  }

  public function down(): void {
    Schema::dropIfExists('qr_tokens');
  }
};