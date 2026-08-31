<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\ArrayMultipleSheetsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Satu pintu export laporan (Fase 3A §1): PDF (dompdf) & Excel (maatwebsite).
 * Data sama persis dengan tampilan (single source getReportData()).
 */
final class ReportExporter
{
    /**
     * @param  array<int, array{title: string, headers: array<int, string>, rows: array<int, array<int, mixed>>, options?: array{totalRows?: int, currencyColumns?: array<int, int>}}>  $sheets
     */
    public static function excel(string $fileName, array $sheets): BinaryFileResponse
    {
        return (new ArrayMultipleSheetsExport($sheets))->download($fileName);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function pdf(string $fileName, View $view, array $data = []): Response
    {
        $pdf = Pdf::loadView($view->name(), array_merge($data, $view->getData()))
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);

        return $pdf->download($fileName);
    }
}
