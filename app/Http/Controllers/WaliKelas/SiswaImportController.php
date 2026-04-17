<?php

namespace App\Http\Controllers\WaliKelas;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\UploadController as AdminUploadController;
use App\Http\Requests\WaliKelas\Siswa\PreviewImportRequest;
use App\Http\Requests\WaliKelas\Siswa\CommitImportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use App\Imports\SiswaImport;
use App\Models\Upload;
use App\Support\HomeroomContext;

class SiswaImportController extends Controller {
  public function index(Request $request) {
    $title = 'Import Data Siswa';

    $template = Upload::query()
      ->where('description', 'like', '%template_import_data_siswa%')
      ->latest()
      ->first();

    $fileName = $template?->file_name ?? 'No file uploaded';
    $fileUrl  = $template ? route('uploads.download', $template->id) : null;

    return view('wali-kelas.siswa.import.index', compact('title', 'fileName', 'fileUrl'));
  }

  public function preview(PreviewImportRequest $request) {
    $path = $request->file('file')->store('tmp/import_siswa');

    $rows = [];
    $headers = [];

    Excel::import(new class($rows, $headers) implements ToCollection, WithHeadingRow {
      public array $rowsRef;
      public array $headersRef;

      public function __construct(&$rowsRef, &$headersRef) {
        $this->rowsRef = &$rowsRef;
        $this->headersRef = &$headersRef;
      }

      public function collection(Collection $collection) {
        $clean = $collection->map(function ($row) {
          $arr = $row->toArray();
          foreach ($arr as $k => $v) {
            if (is_string($v)) {
              $v = trim($v);
              $arr[$k] = ($v === '') ? null : $v;
            }
          }
          return $arr;
        })
        ->filter(function ($row) {
          foreach ($row as $v) {
            if (!is_null($v) && !(is_string($v) && trim($v) === '')) {
              return true;
            }
          }
          return false;
        })
        ->values();

        $this->headersRef = $clean->isNotEmpty()
          ? array_keys($clean->first())
          : [];

        $this->rowsRef = $clean->all();
      }

      public function headingRow(): int {
        return 1;
      }
    }, $path);

    $title = 'Preview Import Siswa';
    $tempPath = $path;

    return view('wali-kelas.siswa.import.preview', compact(
      'title',
      'tempPath',
      'rows',
      'headers'
    ));
  }

  public function commit(CommitImportRequest $request) {
    
    $tempPath = $request->input('tempPath');

    /** @var HomeroomAssignment|null $homeroom */
    $homeroom = $request->attributes->get('homeroom');
    abort_if(!$homeroom || !$homeroom->classroom, 403, 'Anda belum memiliki kelas binaan.');

    $forcedClassroomId = (int) $homeroom->classroom->id;

    // ✅ Term konsisten: pakai HomeroomContext (request attr/session/cache/db)
    // Kalau homeroom->term_id ada, ini harusnya sama dengan activeTerm, tapi kita tetap utamakan homeroom agar konsisten dengan assignment.
    $forcedTermId = (int) ($homeroom->term_id ?: HomeroomContext::activeTermId($request));
    abort_if(!$forcedTermId, 500, 'Term aktif belum tersedia.');

    $import = new SiswaImport($forcedClassroomId, $forcedTermId);

    try {
      Excel::import($import, $tempPath);
    } finally {
      Storage::delete($tempPath);
    }
    
    $failures = $import->failures(); // array of Failure objects
    $failedCount = is_countable($failures) ? count($failures) : 0;
    
    // Ringkasan hasil (optional tapi sangat membantu)
    $created = method_exists($import, 'getImportedCount') ? $import->getImportedCount() : 0;
    $updated = method_exists($import, 'getUpdatedCount') ? $import->getUpdatedCount() : 0;
    $skipped = method_exists($import, 'getSkippedCount') ? $import->getSkippedCount() : 0;
  
    // ✅ Kalau ada kegagalan (sebagian / seluruh) => kembali ke halaman import + alert warning
    if ($failedCount > 0) {
  
      // Format teks multi-line agar gampang ditampilkan
      $lines = [];
      $lines[] = "Sebagian data gagal diimpor.";
      $lines[] = "Ringkasan: Baru {$created}, Update {$updated}, Lewati {$skipped}, Gagal {$failedCount}.";
      $lines[] = "";
      $lines[] = "Detail kegagalan:";
  
      foreach ($failures as $f) {
        // $f->row() => nomor baris excel (headingRow dihitung), $f->attribute() => kolom, $f->errors() => pesan
        $rowNo = $f->row();
        $attr  = $f->attribute();
        $errs  = implode(', ', $f->errors());
  
        // contoh: "Baris 12 - nis: The nis field is required."
        $lines[] = "- Baris {$rowNo} - {$attr}: {$errs}";
      }
      
      return redirect()
        ->route('siswa.import')
        ->with('import_summary', compact('created','updated','skipped','failedCount'))
        ->with('import_failures', collect($failures)->map(function($f){
          return [
            'row' => $f->row(),
            'attribute' => $f->attribute(),
            'errors' => $f->errors(),
            'values' => $f->values(), // kalau mau tampilkan isi baris
          ];
        })->all());
    }
  
    // ✅ Kalau tidak ada kegagalan => redirect ke index + toast success
    $msg = "Import selesai. Baru {$created}, Update {$updated}, Lewati {$skipped}.";
    return redirect()
      ->route('siswa.index')
      ->with('success', $msg);
  }

  public function downloadTemplate() {
    $upload = Upload::query()
      ->where('description', 'like', '%template_import_siswa%')
      ->latest()
      ->first();

    if ($upload) {
      return app(AdminUploadController::class)->download($upload);
    }

    $publicPath = 'uploads/template_import_siswa.xlsx';
    if (Storage::disk('public')->exists($publicPath)) {
      return Storage::disk('public')->download($publicPath, 'Template_Import_Siswa.xlsx');
    }

    abort(404, 'Template tidak ditemukan.');
  }
}