<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\ImportSummaryDTO;
use App\Exports\Templates\EventScheduleTemplateExport;
use App\Exports\Templates\FinanceMasterTemplateExport;
use App\Exports\Templates\MemberFamilyTemplateExport;
use App\Exports\Templates\OfficialMinistryTemplateExport;
use App\Imports\EventScheduleImport;
use App\Imports\FinanceMasterImport;
use App\Imports\MemberFamilyImport;
use App\Imports\OfficialMinistryImport;
use App\Models\AuditLog;
use App\Models\Church;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataMigrationController extends Controller
{
    /**
     * Unduh template resmi Excel (.xlsx) untuk masing-masing modul.
     */
    public function downloadTemplate(string $module): BinaryFileResponse
    {
        $user = auth()->user();
        abort_unless(
            $user && in_array($user->role, ['super_admin', 'church_admin'], true),
            403,
            'Anda tidak memiliki akses untuk mengunduh template migrasi data.'
        );

        return match ($module) {
            'jemaat' => Excel::download(new MemberFamilyTemplateExport(), 'template-jemaat-keluarga.xlsx'),
            'keuangan' => Excel::download(new FinanceMasterTemplateExport(), 'template-master-keuangan.xlsx'),
            'pelayan' => Excel::download(new OfficialMinistryTemplateExport(), 'template-master-pelayan.xlsx'),
            'acara' => Excel::download(new EventScheduleTemplateExport(), 'template-master-acara-jadwal.xlsx'),
            default => abort(404, 'Modul migrasi tidak ditemukan.'),
        };
    }

    /**
     * Eksekusi impor spreadsheet data per modul.
     */
    public function import(Request $request, string $module): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            $user && in_array($user->role, ['super_admin', 'church_admin'], true),
            403,
            'Anda tidak memiliki wewenang untuk mengimpor data spreadsheet.'
        );

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:xlsx,xls',
            ],
            'church_id' => [
                'nullable',
                'integer',
                'exists:churches,id',
            ],
        ], [
            'file.required' => 'File spreadsheet Excel (.xlsx/.xls) wajib diunggah.',
            'file.mimes' => 'Format file wajib berupa file spreadsheet Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file spreadsheet maksimal 10MB.',
        ]);

        // Resolusi Isolasi Multi-Tenant (Vera's Security Checklist)
        if ($user->role === 'church_admin') {
            $churchId = (int) $user->church_id;
        } else {
            // Super Admin dapat memilih church_id target, fallback ke gereja pertama
            $churchId = (int) ($request->input('church_id') ?: $user->church_id ?: Church::query()->value('id'));
        }

        $file = $request->file('file');

        /** @var MemberFamilyImport|FinanceMasterImport|OfficialMinistryImport|EventScheduleImport $importer */
        $importer = match ($module) {
            'jemaat' => new MemberFamilyImport($churchId),
            'keuangan' => new FinanceMasterImport($churchId),
            'pelayan' => new OfficialMinistryImport($churchId),
            'acara' => new EventScheduleImport($churchId),
            default => abort(404, 'Modul migrasi data tidak dikenal.'),
        };

        Excel::import($importer, $file);

        /** @var ImportSummaryDTO $summary */
        $summary = $importer->getSummary();

        // Pencatatan Audit Trail
        AuditLog::create([
            'user_id' => $user->id,
            'church_id' => $churchId,
            'action' => 'DATA_MIGRATION_IMPORT',
            'auditable_type' => $importer::class,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => [
                'module' => $module,
                'total_read' => $summary->totalRowsRead,
                'total_created' => $summary->totalCreated,
                'total_updated' => $summary->totalUpdated,
                'total_skipped' => $summary->totalSkipped,
                'errors_count' => count($summary->errors),
                'church_id' => $churchId,
                'original_filename' => $file->getClientOriginalName(),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $summary->isSuccess(),
                'total_read' => $summary->totalRowsRead,
                'total_created' => $summary->totalCreated,
                'total_updated' => $summary->totalUpdated,
                'total_skipped' => $summary->totalSkipped,
                'errors' => $summary->errors,
            ]);
        }

        return back()->with('import_summary', [
            'module' => $module,
            'success' => $summary->isSuccess(),
            'total_read' => $summary->totalRowsRead,
            'total_created' => $summary->totalCreated,
            'total_updated' => $summary->totalUpdated,
            'total_skipped' => $summary->totalSkipped,
            'errors' => $summary->errors,
        ]);
    }
}
