<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;

use App\Http\Requests\WaliKelas\AuditAttendance\{IndexRequest, DataRequest, ExportRequest};
use App\Queries\WaliKelas\AuditAttendance\WaliAuditAttendanceDataQuery;
use App\Services\WaliKelas\WaliAuditAttendanceExportService;

class AuditAttendanceController extends Controller {
  public function __construct(
    private WaliAuditAttendanceDataQuery $dataQuery,
    private WaliAuditAttendanceExportService $exportService
  ) {}

  /** GET /wali-kelas/audit/attendance */
  public function index(IndexRequest $request) {
    $homeroom = $request->homeroom();
    $classroom = $homeroom->classroom;

    $title  = 'Audit Presensi Kelas';
    $today  = now()->toDateString();
    $date   = $request->selectedDate();
    $status = $request->status();

    return view('wali-kelas.audit.attendance.index', compact(
      'title', 'today', 'date', 'status', 'classroom'
    ));
  }

  /** GET /wali-kelas/audit/attendance/data */
  public function data(DataRequest $request) {
    $homeroom = $request->homeroom();

    $data = $this->dataQuery->get(
      termId: (int) $homeroom->term_id,
      classroomId: (int) $homeroom->classroom_id,
      date: $request->selectedDate(),
      status: $request->status(),
      q: $request->q()
    );

    return response()->json($data);
  }

  /** GET /wali-kelas/audit/attendance/export */
  public function export(ExportRequest $request) {
    $homeroom = $request->homeroom();

    return $this->exportService->download(
      termId: (int) $homeroom->term_id,
      classroomId: (int) $homeroom->classroom_id,
      classroomName: (string) ($homeroom->classroom?->nama_kelas ?? 'Kelas'),
      date: $request->selectedDate(),
      status: $request->status()
    );
  }
}