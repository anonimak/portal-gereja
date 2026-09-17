<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Pages;

use App\DTO\ImportSummaryDTO;
use App\Filament\Clusters\System\SystemCluster;
use App\Imports\EventScheduleImport;
use App\Imports\FinanceMasterImport;
use App\Imports\MemberFamilyImport;
use App\Imports\OfficialMinistryImport;
use App\Models\AuditLog;
use App\Models\Church;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class DataMigrationPage extends Page
{
    use WithFileUploads;

    protected static ?string $cluster = SystemCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Migrasi Data Excel';

    protected static ?string $title = 'Pusat Migrasi Data Excel Terpadu';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.data-migration-page';

    public string $activeTab = 'jemaat';

    public ?int $targetChurchId = null;

    /**
     * @var TemporaryUploadedFile|null
     */
    public $file = null;

    /**
     * @var array{module: string, success: bool, total_read: int, total_created: int, total_updated: int, total_skipped: int, errors: array<int, array{row: int, column: string, value: mixed, message: string}>}|null
     */
    public ?array $importSummary = null;

    public static function canAccess(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['super_admin', 'church_admin'], true);
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->role === 'church_admin') {
            $this->targetChurchId = (int) $user->church_id;
        } else {
            $this->targetChurchId = $user?->church_id ?: (int) Church::query()->value('id');
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->file = null;
        $this->importSummary = null;
    }

    /**
     * @return Collection<int, Church>
     */
    public function getChurchesProperty(): Collection
    {
        return Church::query()->orderBy('name')->get();
    }

    public function getTargetChurchProperty(): ?Church
    {
        return Church::find($this->targetChurchId);
    }

    public function downloadTemplate(string $module)
    {
        return redirect()->route('data-migration.template', ['module' => $module]);
    }

    public function processImport(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && in_array($user->role, ['super_admin', 'church_admin'], true),
            403,
            'Anda tidak memiliki akses untuk mengimpor data.'
        );

        $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls,csv,txt'],
        ], [
            'file.required' => 'Pilih file Excel (.xlsx) terlebih dahulu sebelum memulai proses impor.',
            'file.mimes' => 'Format file harus berupa spreadsheet Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file spreadsheet maksimal 10MB.',
        ]);

        // Isolasi multi-tenant mutlak
        if ($user->role === 'church_admin') {
            $this->targetChurchId = (int) $user->church_id;
        }

        $churchId = (int) $this->targetChurchId;
        if ($churchId <= 0) {
            Notification::make()
                ->title('Gereja Target Belum Dipilih')
                ->body('Silakan pilih gereja tujuan migrasi data terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        /** @var MemberFamilyImport|FinanceMasterImport|OfficialMinistryImport|EventScheduleImport $importer */
        $importer = match ($this->activeTab) {
            'jemaat' => new MemberFamilyImport($churchId),
            'keuangan' => new FinanceMasterImport($churchId),
            'pelayan' => new OfficialMinistryImport($churchId),
            'acara' => new EventScheduleImport($churchId),
            default => abort(400, 'Modul migrasi tidak sah.'),
        };

        $originalName = $this->file->getClientOriginalName();
        $realPath = $this->file->getRealPath();

        Excel::import($importer, $realPath);

        /** @var ImportSummaryDTO $summary */
        $summary = $importer->getSummary();

        // Audit Trail Logging
        AuditLog::create([
            'user_id' => $user->id,
            'church_id' => $churchId,
            'action' => 'DATA_MIGRATION_IMPORT',
            'auditable_type' => $importer::class,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => [
                'module' => $this->activeTab,
                'total_read' => $summary->totalRowsRead,
                'total_created' => $summary->totalCreated,
                'total_updated' => $summary->totalUpdated,
                'total_skipped' => $summary->totalSkipped,
                'errors_count' => count($summary->errors),
                'church_id' => $churchId,
                'original_filename' => $originalName,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->importSummary = [
            'module' => $this->activeTab,
            'success' => $summary->isSuccess(),
            'total_read' => $summary->totalRowsRead,
            'total_created' => $summary->totalCreated,
            'total_updated' => $summary->totalUpdated,
            'total_skipped' => $summary->totalSkipped,
            'errors' => $summary->errors,
        ];

        $this->file = null;

        if ($summary->isSuccess()) {
            Notification::make()
                ->title('Impor Selesai Berhasil')
                ->body("Berhasil memproses {$summary->totalRowsRead} baris: {$summary->totalCreated} data baru ditambahkan, {$summary->totalUpdated} data diperbarui.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Impor Selesai dengan Beberapa Galat')
                ->body("Diproses {$summary->totalRowsRead} baris: {$summary->totalCreated} baru, {$summary->totalUpdated} diperbarui, {$summary->totalSkipped} baris bermasalah.")
                ->warning()
                ->send();
        }
    }
}
