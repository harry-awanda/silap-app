<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

  public function up(): void {
    $exists = DB::table('roles')
      ->where('name', 'superadmin')
      ->where('guard_name', 'web')
      ->exists();

    if (!$exists) {
      DB::table('roles')->insert([
        'name' => 'superadmin',
        'guard_name' => 'web',
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }

  public function down(): void {
    $role = DB::table('roles')
      ->where('name', 'superadmin')
      ->where('guard_name', 'web')
      ->first();

    if ($role && !DB::table('model_has_roles')->where('role_id', $role->id)->exists()) {
      DB::table('roles')->where('id', $role->id)->delete();
    }
  }
};
