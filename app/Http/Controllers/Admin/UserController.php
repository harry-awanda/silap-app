<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller {
  public function index(Request $request) {
    $title = 'Manajemen User';

    // Filter opsional berdasarkan role (dropdown di index)
    $filterRole = $request->query('role'); // contoh: ?role=guru

    $usersQuery = User::query()
    ->with('roles') // untuk tampilkan badges role
    ->orderBy('name');

    // Jika ingin hanya “user staf” (bukan siswa), batasi di sini:
    $usersQuery->whereHas('roles', fn($q) => $q->whereIn('name', ['superadmin','admin','guru','kesiswaan','guru_bk','guru_piket']));

    if ($filterRole) {
      $usersQuery->whereHas('roles', fn($q) => $q->where('name', $filterRole));
    }

    $users = $usersQuery->get();

    // Untuk pilihan role di filter dan form
    $allRoles = Role::orderBy('name')->get();

    return view('admin.users.index', compact('title','users','allRoles','filterRole'));
  }

  public function create() {
    $title = 'Tambah User';
    $allRoles = Role::orderBy('name')->get();

    return view('admin.users.create', compact('title','allRoles'));
  }

  public function store(Request $request) {
    $validated = $request->validate([
      'name'     => ['required','string','max:255'],
      'username' => ['required','string','max:255','unique:users,username'],
      'email'    => ['nullable','email','max:255','unique:users,email'],
      'password' => ['required','string','min:8','confirmed'],
      'roles'    => ['required','array','min:1'],
      'roles.*'  => ['string','exists:roles,name'],
    ]);

    $user = User::create([
      'name'     => $validated['name'],
      'username' => $validated['username'],
      'email'    => $validated['email'] ?? null,
      'password' => Hash::make($validated['password']),
    ]);

    $user->syncRoles($validated['roles']);

    return redirect()->route('admin.users.index')->with('success','User berhasil dibuat.');
  }

  public function edit(User $user) {
    $title = 'Ubah User';
    $allRoles = Role::orderBy('name')->get();
    $user->load('roles'); // hanya optimisasi kecil
    $userRoles = $user->getRoleNames()->toArray();

    return view('admin.users.edit', compact('title','user','allRoles','userRoles'));
  }

  public function update(Request $request, User $user) {
    $validated = $request->validate([
      'name'     => ['required','string','max:255'],
      'username' => ['required','string','max:255', Rule::unique('users','username')->ignore($user->id)],
      'email'    => ['nullable','email','max:255', Rule::unique('users','email')->ignore($user->id)],
      'password' => ['nullable','string','min:8','confirmed'],
      'roles'    => ['required','array','min:1'],
      'roles.*'  => ['string','exists:roles,name'],
    ]);

    $payload = [
      'name'     => $validated['name'],
      'username' => $validated['username'],
      'email'    => $validated['email'] ?? null,
    ];
    if (!empty($validated['password'])) {
      $payload['password'] = Hash::make($validated['password']);
    }
    $user->update($payload);

    // Proteksi: admin/superadmin tidak boleh mencabut role kunci dari dirinya sendiri
    if (auth()->id() === $user->id) {
      $hadAdmin = $user->hasRole('admin');
      $incomingHasAdmin = in_array('admin', $validated['roles'], true);
      if ($hadAdmin && !$incomingHasAdmin) {
        return back()->with('warning','Anda tidak boleh menghapus role admin dari akun Anda sendiri.')->withInput();
      }

      $hadSuperadmin = $user->hasRole('superadmin');
      $incomingHasSuperadmin = in_array('superadmin', $validated['roles'], true);
      if ($hadSuperadmin && !$incomingHasSuperadmin) {
        return back()->with('warning','Anda tidak boleh menghapus role superadmin dari akun Anda sendiri.')->withInput();
      }
    }

    $user->syncRoles($validated['roles']);

    return redirect()->route('admin.users.index')->with('success','User berhasil diperbarui.');
  }

  public function destroy(User $user) {
    // Opsional: cegah user menghapus dirinya sendiri
    if (auth()->id() === $user->id) {
      return back()->with('warning','Anda tidak boleh menghapus akun Anda sendiri.');
    }

    $user->delete();
    return redirect()->route('admin.users.index')->with('success','User berhasil dihapus.');
  }
}
