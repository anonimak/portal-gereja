<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Endpoint export Warta Jemaat untuk tombol UI (kontrak Pixel #14):
 * POST /admin/warta-jemaat/export-{pdf|excel} dengan start_date & end_date.
 */
class WartaJemaatExportController extends Controller
{
    public function pdf(Request $request)
    {
        return $this->build($request)->downloadPdf();
    }

    public function excel(Request $request)
    {
        return $this->build($request)->downloadExcel();
    }

    private function build(Request $request): WartaJemaat
    {
        abort_unless(
            in_array($request->user()?->role, ['super_admin', 'church_admin', 'warta_editor'], true),
            403,
            'Tidak diizinkan mengekspor warta.'
        );

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $page = new WartaJemaat;
        $page->startDate = Carbon::parse($data['start_date'])->startOfDay();
        $page->endDate = Carbon::parse($data['end_date'])->endOfDay();

        return $page;
    }
}
