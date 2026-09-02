<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MemberSacrament;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Fase 3B T8 — Penerbitan Dokumen Sidi / Dokumen Baptis (baptis dewasa).
 *
 * GET /admin/sakramen/sidi/{sacrament}/export-pdf
 * Guard: role panel (super_admin/church_admin) + isolasi tenant + policy.
 * Record type 'sidi' -> "Dokumen Sidi"; type 'baptis_dewasa' -> "Dokumen Baptis".
 */
class SidiExportController extends Controller
{
    /**
     * Label gender untuk dokumen (nilai DB: enum 'm'/'f').
     */
    public static function genderLabel(?string $gender): string
    {
        return match ($gender) {
            'm' => 'Laki-laki',
            'f' => 'Perempuan',
            default => $gender ?? '',
        };
    }

    public function pdf(Request $request, int $sacrament)
    {
        $user = $request->user();

        abort_unless(
            in_array($user?->role, ['super_admin', 'church_admin'], true),
            403,
            'Tidak diizinkan menerbitkan dokumen sidi/baptis.'
        );

        // Hanya nonaktifkan scope church (record gereja lain ditolak 403 eksplisit),
        // TETAPI pertahankan SoftDeletingScope -> record trashed -> 404 (MED-1 Vera).
        $record = MemberSacrament::query()
            ->withoutGlobalScope('church')
            ->with(['member.family', 'official', 'program'])
            ->findOrFail($sacrament);

        if (! in_array($record->type, ['sidi', 'baptis_dewasa'], true)) {
            abort(422, 'Sakramen ini bukan Sidi/Baptis Dewasa.');
        }

        if ($user->role !== 'super_admin' && $record->church_id !== $user->church_id) {
            abort(403, 'Data gereja lain.');
        }

        $member = $record->member;

        $data = [
            'churchName' => $record->church?->name,
            'churchAddress' => $record->church?->address,
            'churchLocation' => $record->church?->address,
            'certificateNumber' => $record->certificate_number,
            'documentTitle' => $record->type === 'sidi' ? 'Dokumen Sidi' : 'Dokumen Baptis',
            'memberName' => $member?->full_name,
            'gender' => self::genderLabel($member?->gender),
            'birthPlace' => $member?->birth_place,
            'birthDate' => $member?->birth_date?->format('d M Y'),
            'fatherName' => $member?->family?->members
                ?->first(fn ($m) => $m->family_relation === 'kepala_keluarga')?->full_name,
            'motherName' => $member?->family?->members
                ?->first(fn ($m) => $m->family_relation === 'istri')?->full_name,
            'sacramentDate' => $record->sacrament_date?->format('d M Y'),
            'issuedAt' => $record->issued_at?->format('d M Y'),
            'ministerName' => $record->official?->display_name,
            'programName' => $record->program?->title,
            'recordTypeLabel' => $record->type === 'sidi' ? 'Sidi' : 'Baptis',
        ];

        $view = View::make('pdf.dokumen-sidi', $data);

        return ReportExporter::pdf('dokumen-'.$record->type.'-'.$record->id.'.pdf', $view, $data);
    }
}
