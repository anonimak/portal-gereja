<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Import/Export CSV Jemaat (Members + Family) — Task slot 07:00 Jumat 4 Sep.
 *
 * Template : GET  /admin/csv-jemaat/template (unduh format template CSV)
 * Export   : GET  /admin/csv-jemaat/export   (?church_id= utk super_admin)
 * Import   : POST /admin/csv-jemaat/import   (file upload .csv)
 *
 * Guard role + isolasi tenant:
 *  - church_admin & jemaat_admin: selalu gereja sendiri.
 *  - super_admin: boleh pilih gereja via ?church_id (tanpa itu = semua gereja, export only).
 *  - finance_admin & role non-panel: ditolak (403).
 *
 * Kolom template (baris header wajib):
 *  nik,full_name,gender,birth_date,family_number,family_relation,status
 * gender: m/f (kosong = tidak diisi). status: aktif|titipan|pindah|meninggal.
 * family_relation: kepala_keluarga|istri|anak|lainnya.
 * Upsert member per (church_id, nik) — import kedua kalinya memperbarui data lama.
 */
class MemberCsvController extends Controller
{
    /** Role yang boleh membaca/mengekspor data jemaat. */
    private const EXPORT_ROLES = ['super_admin', 'church_admin', 'jemaat_admin', 'report_viewer'];

    /** Role yang boleh menulis/import data jemaat. */
    private const IMPORT_ROLES = ['super_admin', 'church_admin'];

    /** Header CSV (urutan tetap = template). */
    private const HEADERS = [
        'nik', 'full_name', 'gender', 'birth_date',
        'family_number', 'family_relation', 'status',
    ];

    private const GENDERS = ['m', 'f'];

    private const STATUSES = ['aktif', 'titipan', 'pindah', 'meninggal'];

    private const RELATIONS = ['kepala_keluarga', 'istri', 'anak', 'lainnya'];

    /**
     * Unduh template CSV kosong beserta contoh baris data jemaat.
     */
    public function template(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            in_array($user?->role, array_unique([...self::EXPORT_ROLES, ...self::IMPORT_ROLES]), true),
            403,
            'Tidak diizinkan mengunduh template CSV.'
        );

        $csv = "\xEF\xBB\xBF".implode(',', self::HEADERS)."\n"
            ."3171010101000001,Budi Santoso,m,1990-01-01,KK-001,kepala_keluarga,aktif\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-jemaat.csv"',
        ]);
    }

    public function export(Request $request): Response
    {
        $user = $request->user();

        abort_unless(
            in_array($user?->role, self::EXPORT_ROLES, true),
            403,
            'Tidak diizinkan mengekspor data jemaat.'
        );

        $church = $this->resolveChurch($request, allowAll: $user->role === 'super_admin');

        // BOM supaya Excel mengenali UTF-8.
        $csv = "\xEF\xBB\xBF".implode(',', self::HEADERS)."\n";

        $query = Member::query()
            ->withoutGlobalScope('church')
            ->select(['members.id_card_number', 'members.full_name', 'members.gender', 'members.birth_date', 'families.family_number', 'members.family_relation', 'members.status'])
            ->leftJoin('families', 'families.id', '=', 'members.family_id')
            ->orderBy('members.id');

        if ($church !== null) {
            $query->where('members.church_id', $church->id);
        }

        $rows = [];
        $query->chunk(500, function ($members) use (&$rows): void {
            foreach ($members as $m) {
                $rows[] = [
                    (string) $m->id_card_number,
                    (string) $m->full_name,
                    (string) ($m->gender ?? ''),
                    $m->birth_date?->format('Y-m-d') ?? '',
                    (string) ($m->family_number ?? ''),
                    (string) $m->family_relation,
                    (string) $m->status,
                ];
            }
        });

        foreach ($rows as $row) {
            $csv .= $this->csvLine($row);
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="jemaat-'.date('Ymd-His').'.csv"',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            in_array($user?->role, self::IMPORT_ROLES, true),
            403,
            'Tidak diizinkan mengimpor data jemaat.'
        );

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        $church = $this->resolveChurch($request, allowAll: false);
        abort_if($church === null, 422, 'church_id wajib untuk import.');

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        abort_if($handle === false, 422, 'File CSV tidak dapat dibaca.');

        try {
            $header = fgetcsv($handle);

            // Strip UTF-8 BOM jika ada di awal header
            if ($header !== false && isset($header[0])) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            }

            if ($header === false || array_map('trim', $header) !== self::HEADERS) {
                throw ValidationException::withMessages([
                    'file' => 'Header CSV tidak sesuai template. Wajib: '.implode(',', self::HEADERS),
                ]);
            }

            $rows = [];
            $seenNik = [];

            while (($row = fgetcsv($handle)) !== false) {
                $row = array_map('trim', $row);
                if (count($row) === 1 && ($row[0] === '' || $row[0] === null)) {
                    continue; // baris kosong
                }
                if (count(array_filter($row, fn ($val) => $val !== '')) === 0) {
                    continue; // semua kolom kosong
                }
                $rows[] = $row;
            }

            if ($rows === []) {
                throw ValidationException::withMessages(['file' => 'CSV kosong (tidak ada baris data).']);
            }

            // Pass 1 — validasi semua baris (all-or-nothing, tanpa partial write).
            $validated = [];
            $errors = [];

            foreach ($rows as $i => $row) {
                $line = $i + 2; // +1 header
                $data = array_combine(self::HEADERS, array_pad($row, count(self::HEADERS), ''));

                if ($data['nik'] === '') {
                    $errors[] = "Baris {$line}: kolom nik wajib diisi.";

                    continue;
                }
                if (isset($seenNik[$data['nik']])) {
                    $errors[] = "Baris {$line}: nik {$data['nik']} duplikat di dalam file.";

                    continue;
                }
                $seenNik[$data['nik']] = true;

                if ($data['full_name'] === '') {
                    $errors[] = "Baris {$line}: full_name wajib diisi.";

                    continue;
                }
                if ($data['family_number'] === '') {
                    $errors[] = "Baris {$line}: family_number wajib diisi.";

                    continue;
                }
                if ($data['gender'] !== '' && ! in_array($data['gender'], self::GENDERS, true)) {
                    $errors[] = "Baris {$line}: gender harus m/f.";

                    continue;
                }
                if ($data['status'] !== '' && ! in_array($data['status'], self::STATUSES, true)) {
                    $errors[] = "Baris {$line}: status harus ".implode('|', self::STATUSES).'.';

                    continue;
                }
                if ($data['family_relation'] !== '' && ! in_array($data['family_relation'], self::RELATIONS, true)) {
                    $errors[] = "Baris {$line}: family_relation harus ".implode('|', self::RELATIONS).'.';

                    continue;
                }
                if ($data['birth_date'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birth_date'])) {
                    $errors[] = "Baris {$line}: birth_date harus format Y-m-d.";

                    continue;
                }

                $validated[] = $data;
            }

            if ($errors !== []) {
                throw ValidationException::withMessages(['file' => implode(' ', array_slice($errors, 0, 8))]);
            }

            // Pass 2 — tulis dalam satu transaksi.
            $imported = 0;
            $updated = 0;

            DB::transaction(function () use ($church, $validated, &$imported, &$updated): void {
                foreach ($validated as $data) {
                    $family = Family::firstOrCreate(
                        ['church_id' => $church->id, 'family_number' => $data['family_number']],
                        ['name' => 'Keluarga '.$data['family_number'], 'address' => '']
                    );

                    $member = Member::query()
                        ->withoutGlobalScope('church')
                        ->where('church_id', $church->id)
                        ->where('id_card_number', $data['nik'])
                        ->withTrashed()
                        ->first();

                    $isNew = $member === null;

                    if ($isNew) {
                        $member = new Member;
                        $member->church_id = $church->id;
                    } elseif ($member->trashed()) {
                        $member->restore();
                    }

                    $member->family_id = $family->id;
                    $member->id_card_number = $data['nik'];
                    $member->full_name = $data['full_name'];
                    $member->gender = $data['gender'] !== '' ? $data['gender'] : $member->gender;
                    $member->birth_date = $data['birth_date'] !== '' ? $data['birth_date'] : $member->birth_date;
                    $member->family_relation = $data['family_relation'] !== '' ? $data['family_relation'] : 'lainnya';
                    $member->status = $data['status'] !== '' ? $data['status'] : 'aktif';
                    $member->save();

                    $isNew ? $imported++ : $updated++;
                }
            });

            return response()->json([
                'ok' => true,
                'imported' => $imported,
                'updated' => $updated,
                'church_id' => $church->id,
            ]);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Resolve gereja target:
     *  - super_admin: gereja pilihan (?church_id) atau null = semua (export).
     *  - selain super_admin: gereja sendiri (ignore input church_id — tenant-safe).
     */
    private function resolveChurch(Request $request, bool $allowAll): ?Church
    {
        $user = $request->user();

        if ($user->role === 'super_admin') {
            $id = $request->integer('church_id');
            if ($id > 0) {
                return Church::query()->findOrFail($id);
            }

            return $allowAll ? null : abort(422, 'church_id wajib untuk super_admin pada operasi ini.');
        }

        return Church::query()->findOrFail($user->church_id);
    }

    /**
     * Encode satu baris CSV (handles quotes/commas).
     *
     * @param  array<int, string>  $fields
     */
    private function csvLine(array $fields): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $fields);
        rewind($handle);
        $line = (string) stream_get_contents($handle);
        fclose($handle);

        return $line;
    }
}
