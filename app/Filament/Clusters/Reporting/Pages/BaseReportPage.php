<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Filament\Clusters\Reporting\ReportingCluster;
use App\Models\Church;
use App\Services\ReportExporter;
use App\Support\ChurchContext;
use App\Traits\HasChurchScope;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Fondasi halaman laporan (Fase 3A §1).
 *
 * Semua halaman memakai single source getReportData() untuk tampilan dan
 * exportBlocks() untuk export (AC-3A-02). Akses via matriks §1.1 (URL → 403).
 */
abstract class BaseReportPage extends Page
{
    use HasChurchScope;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    protected static ?string $cluster = ReportingCluster::class;

    /**
     * Daftar role yang boleh membuka halaman (matriks §1.1).
     *
     * @return array<int, string>
     */
    abstract protected static function allowedRoles(): array;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, static::allowedRoles(), true);
    }

    /**
     * Judul laporan (dipakai di kop & nama file export).
     */
    abstract protected function reportTitle(): string;

    /**
     * Blok data untuk export: [{title, headers, rows}] — single source (AC-3A-02).
     *
     * @return array<int, array{title: string, headers?: array<int, string>, rows: array<int, array<int, mixed>>}>
     */
    abstract protected function exportBlocks(): array;

    protected function pdfExtraData(): array
    {
        return [];
    }

    protected function fileName(string $ext): string
    {
        return sprintf('%s-%s.%s', Str::slug($this->reportTitle()), now()->format('Y-m-d'), $ext);
    }

    public function downloadExcel(): BinaryFileResponse
    {
        return ReportExporter::excel($this->fileName('xlsx'), $this->exportBlocks());
    }

    public function downloadPdf(): Response
    {
        $data = array_merge([
            'churchName' => $this->activeChurchName(),
            'title' => $this->reportTitle(),
            'blocks' => $this->exportBlocks(),
        ], $this->pdfExtraData());

        return ReportExporter::pdf($this->fileName('pdf'), view('pdf.report'), $data);
    }

    // ----- Pemilih gereja super_admin (§9) -----

    public ?int $churchSelect = null;

    public function mount(): void
    {
        $this->churchSelect = ChurchContext::activeChurchId();
    }

    public function updatedChurchSelect(int|string|null $value): void
    {
        if (auth()->user()?->role !== 'super_admin') {
            return;
        }

        ChurchContext::setActiveChurch($value ? (int) $value : null);
        $this->dispatch('church-changed');
    }

    public function canSelectChurch(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public function churchOptions(): array
    {
        return Church::query()
            ->withoutGlobalScopes()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function isAllChurches(): bool
    {
        return ChurchContext::isAll();
    }
}
