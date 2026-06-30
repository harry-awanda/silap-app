<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Exports\SiswaUserExport;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SiswaUserController extends Controller {
  public function index() {
    $title = 'Manajemen Akun Siswa';
    
    $classrooms = DB::table('classrooms')
      ->select('id', 'nama_kelas')
      ->orderBy('nama_kelas')
      ->get();

    return view('admin.users.siswa', compact('title', 'classrooms'));
  }

  public function datatable(Request $request) {
    // Ambil last_activity per user (UNIX timestamp) dari sessions
    $lastActivitySub = DB::table('sessions')
      ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
      ->whereNotNull('user_id')
      ->groupBy('user_id');

    $modelType = (new User)->getMorphClass();
    
    // Query utama:
    $query = User::query()->select([
      'users.id',
      'users.name',
      'users.username',
      'users.password_changed_at',
      DB::raw('la.last_activity as last_activity'),
      'classrooms.nama_kelas as kelas',
    ])
    // last activity
    ->leftJoinSub($lastActivitySub, 'la', function ($join) {
      $join->on('la.user_id', '=', 'users.id');
    })
    // relasi siswa -> classroom
    ->join('siswa', 'siswa.user_id', '=', 'users.id')
    ->join('classrooms', 'classrooms.id', '=', 'siswa.classroom_id')
    // FILTER ROLE MENGGUNAKAN SPATIE:
    ->join('model_has_roles as mhr', function ($join) use ($modelType) {
      $join->on('mhr.model_id', '=', 'users.id')
      ->where('mhr.model_type', '=', $modelType);
    })
    ->join('roles', 'roles.id', '=', 'mhr.role_id')
    ->where('roles.name', '=', 'siswa')
    // ✅ Filter Kelas (by id)
    ->when($request->filled('classroom_id'), function ($q) use ($request) {
      $q->where('classrooms.id', (int) $request->classroom_id);
    })
    // ✅ Filter Password belum pernah diganti
    ->when($request->boolean('never_changed'), function ($q) {
      $q->whereNull('users.password_changed_at');
    });

    return DataTables::of($query)->addIndexColumn()
      ->editColumn('password_changed_at', function ($u) {
        return $u->password_changed_at
          ? Carbon::parse($u->password_changed_at)->format('Y-m-d H:i')
          : '<span class="badge bg-label-warning">Belum pernah</span>';
        })
        ->addColumn('online', function ($u) {
          if (empty($u->last_activity)) {
            return '<span class="badge bg-label-secondary">Offline</span>';
          }
          $last = Carbon::createFromTimestamp((int) $u->last_activity);
          $isOnline = $last->gt(now()->subMinutes(5));
          return $isOnline
            ? '<span class="badge bg-label-success">Online</span>'
            : '<small class="text-muted">Terakhir: ' . e($last->diffForHumans()) . '</small>';
        })
        ->addColumn('actions', function ($u) {
          $resetRoute = route('admin.users.reset-password.temp', $u->id);
          return '
            <div class="dropdown">
              <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="bx bx-dots-vertical-rounded"></i>
              </button>
              <div class="dropdown-menu">
                <form method="POST" action="'.$resetRoute.'" class="d-inline">
                  '.csrf_field().'
                  <button type="submit" class="dropdown-item"
                    onclick="return confirm(\'Reset password dan paksa ganti saat login?\')">
                    <i class="bx bx-key me-2"></i> Reset Password
                  </button>
                </form>
              </div>
            </div>
          ';
        })
    ->rawColumns(['password_changed_at','online','actions'])
    ->make(true);
  }
  
  public function export(Request $request) {
    $classroomId  = $request->filled('classroom_id') ? (int) $request->classroom_id : null;
    $neverChanged = $request->boolean('never_changed');

    $file = 'akun-siswa'
      . ($classroomId ? '-kelas-'.$classroomId : '')
      . ($neverChanged ? '-pwd-belum-diganti' : '')
      . '-' . now()->format('Ymd_His')
      . '.xlsx';

      return Excel::download(new SiswaUserExport($classroomId, $neverChanged), $file);
    }
}