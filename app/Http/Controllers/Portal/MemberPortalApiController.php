<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\OnlineOffering;
use App\Models\User;
use App\Models\WartaPublication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberPortalApiController extends Controller
{
    /**
     * Autentikasi API jemaat (via email atau No. KTP / NIK).
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $password = $credentials['password'];

        $user = null;

        // Coba cari via email
        if (str_contains($login, '@')) {
            $user = User::where('email', $login)->first();
        }

        // Jika belum ketemu, cari via no identitas (id_card_number) member
        if (! $user) {
            $member = Member::withoutGlobalScopes()->where('id_card_number', $login)->first();
            if ($member) {
                $user = User::where('member_id', $member->id)->first();
            }
        }

        // Jika masih belum ketemu, coba cari user berdasarkan email persis
        if (! $user) {
            $user = User::where('email', $login)->first();
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Kredensial tidak valid.',
            ], 401);
        }

        // Generate API token
        $token = Str::random(64);
        $user->api_token = $token;
        $user->save();

        $user->load('church');

        $memberData = null;
        if ($user->member_id) {
            $member = Member::withoutGlobalScopes()->with(['family.members', 'church'])->find($user->member_id);
            if ($member) {
                $memberData = [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'id_card_number' => $member->id_card_number,
                    'family_relation' => $member->family_relation,
                    'status' => $member->status,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'church_id' => $user->church_id,
                'church_name' => $user->church?->name,
                'member_id' => $user->member_id,
            ],
            'member' => $memberData,
        ]);
    }

    /**
     * Logout API jemaat (revokasi token).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->api_token = null;
            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout.',
        ]);
    }

    /**
     * Data user yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('church');

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'church' => $user->church ? [
                        'id' => $user->church->id,
                        'name' => $user->church->name,
                        'code' => $user->church->code,
                    ] : null,
                ],
                'member_id' => $user->member_id,
            ],
        ]);
    }

    /**
     * Profil / Data Diri anggota (detail diri, keluarga, sakramen).
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->member_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun pengguna belum ditautkan dengan data anggota jemaat.',
            ], 404);
        }

        $member = Member::withoutGlobalScopes()
            ->with([
                'church',
                'family.members',
                'sacraments.official',
                'sacraments.marriage',
                'attendances',
            ])
            ->where('church_id', $user->church_id)
            ->find($user->member_id);

        if (! $member) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data anggota jemaat tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->formatMemberData($member),
        ]);
    }

    /**
     * Detail anggota spesifik dengan proteksi anti-IDOR ketat.
     */
    public function showMember(Request $request, Member $member): JsonResponse
    {
        $user = $request->user();

        // Anti-IDOR: jemaat hanya boleh membaca data dirinya sendiri
        if ((int) $user->member_id !== (int) $member->id || (int) $user->church_id !== (int) $member->church_id) {
            abort(403, 'Anda tidak diizinkan mengakses data jemaat lain.');
        }

        $member->load([
            'church',
            'family.members',
            'sacraments.official',
            'sacraments.marriage',
            'attendances',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatMemberData($member),
        ]);
    }

    /**
     * Jadwal ibadah & event mendatang gereja (dengan tanggal, waktu, lokasi).
     */
    public function events(Request $request): JsonResponse
    {
        $user = $request->user();

        // Isolasi tenant: hanya event milik gereja pengguna
        $events = Event::query()
            ->where('church_id', $user->church_id)
            ->where('start_datetime', '>=', now()->startOfDay())
            ->orderBy('start_datetime', 'asc')
            ->with(['category', 'rosters.member', 'rosters.official', 'rosters.role'])
            ->get();

        $memberId = $user->member_id;

        $mappedEvents = $events->map(function (Event $event) use ($memberId): array {
            $isMyDuty = false;
            $rosters = [];

            foreach ($event->rosters as $roster) {
                if ($memberId && (int) $roster->member_id === (int) $memberId) {
                    $isMyDuty = true;
                }

                $rosters[] = [
                    'role' => $roster->role?->name ?? 'Pelayan',
                    'person_name' => $roster->member?->full_name ?? $roster->official?->display_name ?? $roster->official?->external_name ?? 'Belum ditentukan',
                ];
            }

            return [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category?->name,
                'start_datetime' => $event->start_datetime?->toIso8601String(),
                'end_datetime' => $event->end_datetime?->toIso8601String(),
                'formatted_date' => $event->start_datetime?->locale('id')->translatedFormat('l, d F Y'),
                'formatted_time' => $event->start_datetime && $event->end_datetime
                    ? $event->start_datetime->format('H:i').' - '.$event->end_datetime->format('H:i').' WIB'
                    : null,
                'location' => $event->location,
                'is_my_duty' => $isMyDuty,
                'rosters' => $rosters,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $mappedEvents,
        ]);
    }

    /**
     * Detail jadwal ibadah / event dengan isolasi tenant ketat.
     */
    public function showEvent(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        // Isolasi tenant: dilarang melihat event dari gereja lain
        if ((int) $event->church_id !== (int) $user->church_id) {
            abort(404, 'Jadwal ibadah tidak ditemukan.');
        }

        $event->load(['category', 'rosters.member', 'rosters.official', 'rosters.role']);
        $memberId = $user->member_id;

        $isMyDuty = false;
        $rosters = [];

        foreach ($event->rosters as $roster) {
            if ($memberId && (int) $roster->member_id === (int) $memberId) {
                $isMyDuty = true;
            }

            $rosters[] = [
                'role' => $roster->role?->name ?? 'Pelayan',
                'person_name' => $roster->member?->full_name ?? $roster->official?->display_name ?? $roster->official?->external_name ?? 'Belum ditentukan',
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category?->name,
                'start_datetime' => $event->start_datetime?->toIso8601String(),
                'end_datetime' => $event->end_datetime?->toIso8601String(),
                'formatted_date' => $event->start_datetime?->locale('id')->translatedFormat('l, d F Y'),
                'formatted_time' => $event->start_datetime && $event->end_datetime
                    ? $event->start_datetime->format('H:i').' - '.$event->end_datetime->format('H:i').' WIB'
                    : null,
                'location' => $event->location,
                'is_my_duty' => $isMyDuty,
                'rosters' => $rosters,
            ],
        ]);
    }

    /**
     * Warta jemaat gereja (hanya edisi terpublikasi milik gereja pengguna).
     */
    public function warta(Request $request): JsonResponse
    {
        $user = $request->user();

        // Isolasi tenant & hanya yang published
        $publications = WartaPublication::query()
            ->forChurch($user->church_id)
            ->published()
            ->latest('published_at')
            ->get();

        $mapped = $publications->map(function (WartaPublication $pub): array {
            return [
                'id' => $pub->id,
                'title' => $pub->title,
                'period_start' => $pub->period_start?->format('Y-m-d'),
                'period_end' => $pub->period_end?->format('Y-m-d'),
                'period_label' => $pub->periodLabel(),
                'published_at' => $pub->published_at?->toIso8601String(),
                'formatted_published_at' => $pub->published_at?->locale('id')->translatedFormat('d F Y, H:i'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $mapped,
        ]);
    }

    /**
     * Detail warta jemaat terpublikasi (dengan snapshot konten).
     */
    public function showWarta(Request $request, WartaPublication $publication): JsonResponse
    {
        $user = $request->user();

        // Isolasi tenant & validasi status published
        if ((int) $publication->church_id !== (int) $user->church_id
            || $publication->status !== 'published'
            || $publication->published_at === null
            || $publication->published_at->isFuture()) {
            abort(404, 'Warta jemaat tidak ditemukan.');
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $publication->id,
                'title' => $publication->title,
                'period_start' => $publication->period_start?->format('Y-m-d'),
                'period_end' => $publication->period_end?->format('Y-m-d'),
                'period_label' => $publication->periodLabel(),
                'published_at' => $publication->published_at?->toIso8601String(),
                'formatted_published_at' => $publication->published_at?->locale('id')->translatedFormat('d F Y, H:i'),
                'content' => $publication->content,
            ],
        ]);
    }

    /**
     * Helper formatting data member, keluarga, dan sakramen.
     *
     * @return array<string, mixed>
     */
    private function formatMemberData(Member $member): array
    {
        $family = $member->family;
        $familyMembers = [];

        if ($family) {
            foreach ($family->members as $fMember) {
                $familyMembers[] = [
                    'id' => $fMember->id,
                    'full_name' => $fMember->full_name,
                    'gender' => $fMember->gender,
                    'gender_label' => $fMember->gender === 'm' ? 'Laki-laki' : ($fMember->gender === 'f' ? 'Perempuan' : null),
                    'family_relation' => $fMember->family_relation,
                    'family_relation_label' => $this->relationLabel($fMember->family_relation),
                    'birth_date' => $fMember->birth_date?->format('Y-m-d'),
                    'status' => $fMember->status,
                ];
            }
        }

        $sacraments = [];
        foreach ($member->sacraments as $sacrament) {
            $sacraments[] = [
                'id' => $sacrament->id,
                'type' => $sacrament->type,
                'type_label' => $this->sacramentTypeLabel($sacrament->type),
                'sacrament_date' => $sacrament->sacrament_date?->format('Y-m-d'),
                'formatted_date' => $sacrament->sacrament_date?->locale('id')->translatedFormat('d F Y'),
                'certificate_number' => $sacrament->certificate_number,
                'official' => $sacrament->official?->display_name ?? $sacrament->official?->external_name,
                'issued_at' => $sacrament->issued_at?->format('Y-m-d'),
            ];
        }

        return [
            'id' => $member->id,
            'full_name' => $member->full_name,
            'id_card_number' => $member->id_card_number,
            'gender' => $member->gender,
            'gender_label' => $member->gender === 'm' ? 'Laki-laki' : ($member->gender === 'f' ? 'Perempuan' : null),
            'birth_place' => $member->birth_place,
            'birth_date' => $member->birth_date?->format('Y-m-d'),
            'formatted_birth_date' => $member->birth_date?->locale('id')->translatedFormat('d F Y'),
            'age' => $member->birth_date ? $member->birth_date->age : null,
            'family_relation' => $member->family_relation,
            'family_relation_label' => $this->relationLabel($member->family_relation),
            'status' => $member->status,
            'custom_fields' => $member->custom_fields,
            'church' => [
                'id' => $member->church?->id,
                'name' => $member->church?->name,
                'code' => $member->church?->code,
                'address' => $member->church?->address,
            ],
            'family' => $family ? [
                'id' => $family->id,
                'family_number' => $family->family_number,
                'name' => $family->name,
                'address' => $family->address,
                'members' => $familyMembers,
            ] : null,
            'sacraments' => $sacraments,
            'attendances_count' => $member->attendances->count(),
        ];
    }

    /**
     * Riwayat persembahan online milik jemaat login.
     */
    public function offerings(Request $request): JsonResponse
    {
        $user = $request->user();

        $offerings = OnlineOffering::where('church_id', $user->church_id)
            ->where('member_id', $user->member_id)
            ->with(['fund', 'financialCategory'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'offerings' => $offerings->items(),
            'total' => $offerings->total(),
            'current_page' => $offerings->currentPage(),
            'last_page' => $offerings->lastPage(),
        ]);
    }

    /**
     * Submit persembahan online dari API.
     */
    public function storeOffering(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'fund_id' => ['required', 'exists:funds,id'],
            'financial_category_id' => ['required', 'exists:financial_categories,id'],
            'amount' => ['required', 'integer', 'min:10000'],
            'payment_method' => ['required', 'in:qris,bank_transfer,va'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'prayer_notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'image', 'max:5120'],
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('offering-proofs', 'public');
        }

        $referenceCode = OnlineOffering::generateReferenceCode();

        $offering = OnlineOffering::create([
            'church_id' => $user->church_id,
            'member_id' => $user->member_id,
            'fund_id' => $validated['fund_id'],
            'financial_category_id' => $validated['financial_category_id'],
            'donor_name' => $user->member?->full_name ?? $user->name,
            'donor_phone' => $user->member?->phone_number ?? null,
            'donor_email' => $user->email,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'bank_name' => $validated['bank_name'] ?? null,
            'reference_code' => $referenceCode,
            'proof_path' => $proofPath,
            'prayer_notes' => $validated['prayer_notes'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Persembahan online berhasil dikirim dan menunggu verifikasi majelis.',
            'offering' => [
                'id' => $offering->id,
                'reference_code' => $offering->reference_code,
                'amount' => $offering->amount,
                'status' => $offering->status,
            ],
        ], 201);
    }

    private function relationLabel(?string $relation): string
    {
        return match ($relation) {
            'kepala_keluarga' => 'Kepala Keluarga',
            'istri' => 'Istri',
            'anak' => 'Anak',
            default => 'Lainnya',
        };
    }

    private function sacramentTypeLabel(?string $type): string
    {
        return match ($type) {
            'baptis_anak' => 'Baptis Anak',
            'baptis_dewasa' => 'Baptis Dewasa',
            'sidi' => 'Peneguhan Sidi',
            'pernikahan' => 'Pernikahan Kudus',
            default => ucfirst(str_replace('_', ' ', $type ?? 'Sakramen')),
        };
    }
}
