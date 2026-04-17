<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\AttendanceDailyExport;

use App\Http\Requests\AuditAttendance\{IndexRequest, DtRequest, ExportRequest, LateLeaderboardRequest};
use App\Services\AuditAttendance\AuditAttendanceService;
use App\Queries\AuditAttendance\DetailDatatableQuery;
use App\Queries\AuditAttendance\LateLeaderboardQuery;
use App\Support\ActiveTerm;

class AuditAttendanceController extends Controller {
  public function __construct(
    // private ActiveTerm $termResolver,
    private AuditAttendanceService $service,
    private DetailDatatableQuery $detailDt,
    private LateLeaderboardQuery $lateDt,
  ) {}

  public function index(IndexRequest $r) {
    $termId = ActiveTerm::id($r);

    $data = $this->service->pageData(
      termId: $termId,
      date: $r->selectedDate(),
      status: $r->status(),
      kelasId: $r->classroomId()
    );

    return view('audit.attendance.index', $data);
  }

  public function dt(DtRequest $r) {
    try {
      $termId = ActiveTerm::id($r);

      $dt = $this->detailDt->build(
        termId: $termId,
        date: $r->selectedDate(),
        status: $r->status(),
        kelasId: $r->classroomId(),
        request: $r
      );

      // $dt sudah berupa DataTable instance siap ->toJson()
      return $dt->toJson();
    } catch (\Throwable $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  public function export(ExportRequest $r) {
    $termId = ActiveTerm::id($r);

    $date   = $r->selectedDate();
    $status = $r->status();
    $kelasId = $r->classroomId();

    $filename = 'audit-presensi_' . $date . ($status ? ('_' . $status) : '') . '.xlsx';

    return Excel::download(
      new AttendanceDailyExport($termId, $date, $status, $kelasId),
      $filename
    );
  }

  public function lateLeaderboard(LateLeaderboardRequest $r) {
    try {
      $termId = ActiveTerm::id($r);

      $end   = $r->selectedDate();
      $start = Carbon::parse($end)->startOfMonth()->toDateString();

      $dt = $this->lateDt->build(
        termId: $termId,
        start: $start,
        end: $end,
        kelasId: $r->classroomId(),
        request: $r
      );

      return $dt->toJson();
    } catch (\Throwable $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }
}