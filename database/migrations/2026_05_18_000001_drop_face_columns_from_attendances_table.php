<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('attendances', function (Blueprint $table) {
      if (Schema::hasColumn('attendances', 'similarity')) {
        $table->dropColumn('similarity');
      }

      if (Schema::hasColumn('attendances', 'liveness_passed')) {
        $table->dropColumn('liveness_passed');
      }
    });
  }

  public function down(): void {
    Schema::table('attendances', function (Blueprint $table) {
      if (!Schema::hasColumn('attendances', 'similarity')) {
        $table->decimal('similarity', 5, 3)->nullable()->after('auto_marked');
      }

      if (!Schema::hasColumn('attendances', 'liveness_passed')) {
        $table->boolean('liveness_passed')->default(false)->after('similarity');
      }
    });
  }
};
