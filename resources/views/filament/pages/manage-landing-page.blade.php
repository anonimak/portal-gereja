<x-filament-panels::page>
    @if(auth()->user()?->role !== 'super_admin')
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-800/40 dark:bg-amber-950/30 dark:text-amber-300">
            <div class="flex items-center gap-3">
                <x-heroicon-o-information-circle class="h-6 w-6 shrink-0 text-amber-600 dark:text-amber-400" />
                <div>
                    <h4 class="font-semibold">Mode Baca Saja (Read-Only)</h4>
                    <p class="text-sm mt-0.5">
                        Pengaturan CMS Website Resmi Gereja Pusat hanya dapat diubah oleh <strong>Super Admin</strong>. Sebagai Admin Cabang/Kelompok, Anda dapat memperbarui identitas jemaat Anda melalui menu <strong>Gereja / Profil Cabang</strong>.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        @if(auth()->user()?->role === 'super_admin')
            <div class="flex justify-end pt-4">
                <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                    Simpan Pengaturan CMS
                </x-filament::button>
            </div>
        @endif
    </form>
</x-filament-panels::page>
