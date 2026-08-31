<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\Family;
use App\Models\Member;

class LaporanJemaatPage extends BaseReportPage
{
    protected string $view = 'filament.pages.laporan-jemaat';

    protected static ?string $navigationLabel = 'Laporan Jemaat';

    protected static ?string $title = 'Laporan Jemaat / Anggota';

    protected static ?int $navigationSort = 2;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1: super_admin, church_admin, jemaat_admin (view), report_viewer (view).
        return ['super_admin', 'church_admin', 'jemaat_admin', 'report_viewer'];
    }

    public ?string $status = null;

    public ?string $gender = null;

    public ?int $familyId = null;

    public function mount(): void
    {
        parent::mount();
    }

    protected function reportTitle(): string
    {
        return 'Laporan-Jemaat-'.now()->format('m-Y');
    }

    public function getReportData(): array
    {
        $query = $this->scopeToActiveChurch(Member::with('family')->whereNotNull('church_id'));

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->gender) {
            $query->where('gender', $this->gender);
        }

        if ($this->familyId) {
            $query->where('family_id', $this->familyId);
        }

        $members = $query->orderBy('full_name')->get();

        $byStatus = $members->groupBy(fn ($m) => $m->status ?: 'tanpa-status')
            ->map(fn ($g) => $g->count());

        $byGender = $members->groupBy(fn ($m) => $m->gender ?: 'tanpa-gender')
            ->map(fn ($g) => $g->count());

        $families = $members->groupBy(fn ($m) => $m->family_id ?: 0);

        return [
            'churchName' => $this->activeChurchName(),
            'members' => $members,
            'byStatus' => $byStatus,
            'byGender' => $byGender,
            'families' => $families,
            'familyMap' => Family::query()->pluck('name', 'id'),
        ];
    }

    protected function exportBlocks(): array
    {
        $data = $this->getReportData();

        $statusRows = $data['byStatus']->map(fn ($count, $status) => [$status, $count])->values()->all();
        $genderRows = $data['byGender']->map(fn ($count, $gender) => [$gender, $count])->values()->all();

        $detailRows = $data['members']->map(function ($m) use ($data) {
            $familyName = $data['familyMap']->get($m->family_id, '-');

            return [
                $m->id_card_number,
                $m->full_name,
                $m->gender,
                $m->birth_place,
                $m->birth_date?->format('d/m/Y'),
                $familyName,
                $m->family_relation,
                $m->status,
            ];
        })->all();

        return [
            ['title' => 'Ringkasan Status', 'headers' => ['Status', 'Jumlah'], 'rows' => $statusRows],
            ['title' => 'Ringkasan Jenis Kelamin', 'headers' => ['Jenis Kelamin', 'Jumlah'], 'rows' => $genderRows],
            ['title' => 'Detail Per Keluarga', 'headers' => ['NIK', 'Nama', 'JK', 'Tempat Lahir', 'Tgl Lahir', 'Keluarga', 'Hubungan', 'Status'], 'rows' => $detailRows],
        ];
    }
}
