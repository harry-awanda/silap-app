<?php

namespace App\Exports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SiswaUserExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize {
  public function __construct(
    private ?int $classroomId,
    private bool $neverChanged
  ) {}

  public function query() {
    // last_activity per user
    $lastActivitySub = DB::table('sessions')
      ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
      ->whereNotNull('user_id')
      ->groupBy('user_id');

    $modelType = (new User)->getMorphClass();

    return User::query()->select([
        'users.id',
        'users.name',
        'users.username',
        'users.password_changed_at',
        DB::raw('la.last_activity as last_activity'),
        'classrooms.nama_kelas as kelas',
      ])
      ->leftJoinSub($lastActivitySub, 'la', function ($join) {
        $join->on('la.user_id', '=', 'users.id');
      })
      ->join('siswa', 'siswa.user_id', '=', 'users.id')
      ->join('classrooms', 'classrooms.id', '=', 'siswa.classroom_id')
      ->join('model_has_roles as mhr', function ($join) use ($modelType) {
        $join->on('mhr.model_id', '=', 'users.id')
          ->where('mhr.model_type', '=', $modelType);
      })
      ->join('roles', 'roles.id', '=', 'mhr.role_id')
      ->where('roles.name', '=', 'siswa')
      ->when($this->classroomId, function ($q) {
        $q->where('classrooms.id', $this->classroomId);
      })
      ->when($this->neverChanged, function ($q) {
        $q->whereNull('users.password_changed_at');
      })
      ->orderBy('classrooms.nama_kelas')
      ->orderBy('users.name');
  }

  public function headings(): array {
    return [
      'No',
      'Nama',
      'Kelas',
      'Username',
      'Password Diubah',
      'Status Online',
      'Terakhir Aktif',
    ];
  }

  public function map($row): array {
    static $no = 0;
    $no++;

    $pwdChanged = $row->password_changed_at
      ? Carbon::parse($row->password_changed_at)->format('Y-m-d H:i')
      : 'Belum pernah';

    // Online logic sama seperti DataTables
    $onlineText = 'Offline';
    $lastText   = '-';

    if (!empty($row->last_activity)) {
      $last = Carbon::createFromTimestamp((int) $row->last_activity);
      $lastText = $last->format('Y-m-d H:i');

      $onlineText = $last->gt(now()->subMinutes(5)) ? 'Online' : 'Offline';
    }

    return [
      $no,
      $row->name,
      $row->kelas,
      $row->username,
      $pwdChanged,
      $onlineText,
      $lastText,
    ];
  }

  public function styles(Worksheet $sheet) {
    // Header bold
    $sheet->getStyle('A1:G1')->getFont()->setBold(true);

    // Auto width sederhana (opsional)
    foreach (range('A', 'G') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return [];
  }
}