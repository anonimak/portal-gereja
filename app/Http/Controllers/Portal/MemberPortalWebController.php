<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\Event;
use App\Models\Member;
use App\Models\User;
use App\Models\WartaPublication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MemberPortalWebController extends Controller
{
    /**
     * Halaman login portal jemaat.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('portal.profile');
        }

        $churches = Church::query()->orderBy('name')->get();

        return view('portal.login', compact('churches'));
    }

    /**
     * Proses login web (bisa pakai Email atau No. KTP / NIK).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $password = $credentials['password'];

        $user = null;

        // Coba email
        if (str_contains($login, '@')) {
            $user = User::where('email', $login)->first();
        }

        // Coba No. KTP / NIK jemaat
        if (! $user) {
            $member = Member::withoutGlobalScopes()->where('id_card_number', $login)->first();
            if ($member) {
                $user = User::where('member_id', $member->id)->first();
            }
        }

        // Fallback coba email langsung
        if (! $user) {
            $user = User::where('email', $login)->first();
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withErrors([
                'login' => 'Email atau Nomor Identitas dan password tidak cocok.',
            ])->withInput($request->only('login'));
        }

        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('portal.profile'));
    }

    /**
     * Logout web session jemaat.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('status', 'Anda telah berhasil keluar dari Portal Jemaat.');
    }

    /**
     * Dashboard redirect ke profil.
     */
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('portal.profile');
    }

    /**
     * Halaman Profil / Data Diri anggota.
     */
    public function profile(Request $request): View
    {
        $user = $request->user();
        $user->load('church');

        $member = null;
        if ($user->member_id) {
            $member = Member::withoutGlobalScopes()
                ->with([
                    'church',
                    'family.members',
                    'sacraments.official',
                    'sacraments.marriage',
                    'attendances.event',
                ])
                ->where('church_id', $user->church_id)
                ->find($user->member_id);
        }

        return view('portal.profile', compact('user', 'member'));
    }

    /**
     * Halaman Jadwal Ibadah & Event mendatang.
     */
    public function events(Request $request): View
    {
        $user = $request->user();
        $user->load('church');

        $events = Event::query()
            ->where('church_id', $user->church_id)
            ->where('start_datetime', '>=', now()->startOfDay())
            ->orderBy('start_datetime', 'asc')
            ->with(['category', 'rosters.member', 'rosters.official', 'rosters.role'])
            ->paginate(15);

        return view('portal.events', compact('user', 'events'));
    }

    /**
     * Detail Jadwal Ibadah / Event.
     */
    public function showEvent(Request $request, Event $event): View
    {
        $user = $request->user();

        // Isolasi tenant
        abort_if((int) $event->church_id !== (int) $user->church_id, 404);

        $event->load(['category', 'rosters.member', 'rosters.official', 'rosters.role']);

        return view('portal.event-detail', compact('user', 'event'));
    }

    /**
     * Halaman Warta Jemaat terpublikasi.
     */
    public function warta(Request $request): View
    {
        $user = $request->user();
        $user->load('church');

        $publications = WartaPublication::query()
            ->forChurch($user->church_id)
            ->published()
            ->latest('published_at')
            ->paginate(10);

        return view('portal.warta', compact('user', 'publications'));
    }

    /**
     * Detail Warta Jemaat terpublikasi.
     */
    public function showWarta(Request $request, WartaPublication $publication): View
    {
        $user = $request->user();

        // Isolasi tenant & status publikasi
        abort_if(
            (int) $publication->church_id !== (int) $user->church_id
            || $publication->status !== 'published'
            || $publication->published_at === null
            || $publication->published_at->isFuture(),
            404
        );

        return view('portal.warta-detail', compact('user', 'publication'));
    }
}
