<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleController extends Controller {
  public function index()
  {
  $title = 'Roles';

  // Ambil role + hitung jumlah user yang memakai role tsb
  // model_has_roles: role_id, model_type, model_id
  $roles = Role::query()
    ->select('roles.*')
    ->withCount(['users as users_count' => function ($q) {
    // Relasi "users" tak tersedia default, jadi kita join manual via subquery:
    // tapi Spatie punya relation 'users' di Role? Tidak default. Kita pakai subquery count.
    }])
    ->get();

  // Karena Role tidak bawa relasi users by default, kita hitung via query terpisah:
  $counts = DB::table('model_has_roles')
    ->select('role_id', DB::raw('COUNT(*) as c'))
    ->groupBy('role_id')
    ->pluck('c','role_id');

  // Tempelkan secara manual
  $roles->each(function($r) use ($counts){
    $r->users_count = (int) ($counts[$r->id] ?? 0);
  });

  return view('admin.roles.index', compact('title','roles'));
  }

  public function create() {
  $title = 'Tambah Role';
  return view('admin.roles.create', compact('title'));
  }

  public function store(StoreRoleRequest $request) {
  Role::create([
    'name' => $request->input('name'),
    'guard_name' => $request->input('guard_name','web'),
  ]);

  return redirect()->route('admin.roles.index')->with('success','Role berhasil ditambahkan.');
  }

  public function edit(Role $role) {
  $title = 'Ubah Role';
  return view('admin.roles.edit', compact('title','role'));
  }

  public function update(UpdateRoleRequest $request, Role $role) {
  $role->update([
    'name' => $request->input('name'),
    // guard_name tidak diubah (tetap 'web') untuk konsistensi
  ]);

  return redirect()->route('admin.roles.index')->with('success','Role berhasil diperbarui.');
  }

  public function destroy(Role $role) {
  // Lindungi role kunci sistem
  if (in_array($role->name, ['superadmin', 'admin'], true)) {
    return back()->with('warning','Role superadmin/admin tidak boleh dihapus.');
  }

  // Cegah hapus jika masih dipakai user
  $inUse = DB::table('model_has_roles')->where('role_id', $role->id)->exists();
  if ($inUse) {
    return back()->with('warning','Tidak dapat menghapus role karena masih dipakai oleh pengguna.');
  }

  $role->delete();
  return redirect()->route('admin.roles.index')->with('success','Role berhasil dihapus.');
  }
}
