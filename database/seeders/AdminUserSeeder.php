<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder {
  
  public function run(): void {

    $adminEmail    = env('ADMIN_EMAIL', 'admin@example.com');
    $adminName     = env('ADMIN_NAME', 'Administrator');
    $adminUsername = env('ADMIN_USERNAME', 'admin');
    $adminPassword = env('ADMIN_PASSWORD');
    
    if (empty($adminPassword)) {
      $this->command->error('ENV ADMIN_PASSWORD belum diatur. Seeder dibatalkan.');
      $this->command->line('Tambahkan di .env: ADMIN_PASSWORD=your_secure_password');
      return;
    }
    
    // Pastikan role admin ada (guard web)
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    
    // Buat / ambil user
    $user = User::firstOrCreate(
      ['email' => $adminEmail],
      [
        'name' => $adminName,
        'username' => $adminUsername,
        'password' => Hash::make($adminPassword),
      ]
    );

    // Assign role admin
    if (!$user->hasRole('admin')) {
      $user->assignRole($role);
    }

    $this->command->info("Admin siap: {$adminEmail} / {$adminUsername}");
  }
}