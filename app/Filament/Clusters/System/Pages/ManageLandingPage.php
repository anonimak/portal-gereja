<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Pages;

use App\Filament\Clusters\System\SystemCluster;
use App\Models\LandingSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageLandingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = SystemCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'CMS Landing Page';

    protected static ?string $title = 'CMS Website Resmi Gereja';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.manage-landing-page';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['super_admin', 'church_admin'], true);
    }

    public function mount(): void
    {
        $setting = LandingSetting::current(null);

        $formData = $setting->toArray();

        // Transform missions for repeater
        if (isset($formData['missions']) && is_array($formData['missions'])) {
            $formData['missions'] = array_map(function ($item) {
                return is_array($item) ? $item : ['item' => $item];
            }, $formData['missions']);
        } else {
            $formData['missions'] = [];
        }

        $this->form->fill($formData);
    }

    public function form(Schema $form): Schema
    {
        $isSuperAdmin = auth()->user()?->role === 'super_admin';

        return $form
            ->schema([
                Section::make('1. Hero Banner & Tema Tahunan')
                    ->description('Identitas utama gereja pada bagian header dan hero banner.')
                    ->schema([
                        TextInput::make('hero_title')
                            ->label('Nama Utama Gereja')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('hero_subtitle')
                            ->label('Subjudul / Sinode')
                            ->maxLength(255),

                        TextInput::make('hero_tagline')
                            ->label('Tagline / Slogan Gereja')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('theme_verse')
                            ->label('Ayat Tema Tahunan')
                            ->rows(3)
                            ->required(),

                        TextInput::make('theme_verse_ref')
                            ->label('Referensi Ayat Tema')
                            ->placeholder('Roma 12:10')
                            ->maxLength(100),

                        FileUpload::make('hero_banner_path')
                            ->label('Banner Hero (Latar Belakang)')
                            ->disk('public')
                            ->directory('landing/banners')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Format JPG/PNG/WebP, maks 2MB. Dianjurkan resolusi lebar (1920x800).'),
                    ])
                    ->columns(2),

                Section::make('2. Sambutan Gembala / Majelis Jemaat')
                    ->description('Pesan gembala atau majelis jemaat untuk menyapa pengunjung website.')
                    ->schema([
                        TextInput::make('pastoral_greeting_title')
                            ->label('Judul Sambutan')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('pastoral_greeting_author')
                            ->label('Nama Penulis / Pendeta')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('pastoral_greeting_author_role')
                            ->label('Jabatan Penulis')
                            ->required()
                            ->maxLength(255),

                        FileUpload::make('pastoral_greeting_photo_path')
                            ->label('Foto Gembala / Majelis')
                            ->disk('public')
                            ->directory('landing/pastoral')
                            ->image()
                            ->maxSize(1024)
                            ->helperText('Format JPG/PNG, rasio 1:1 atau potret, maks 1MB.'),

                        Textarea::make('pastoral_greeting_content')
                            ->label('Isi Pesan Sambutan')
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('3. Foto Latar Belakang Parallax Setiap Seksi')
                    ->description('Pengaturan gambar latar belakang parallax untuk masing-masing seksi halaman depan. Format JPG/PNG/WebP resolusi tinggi (1920px), maks 2MB per gambar.')
                    ->schema([
                        FileUpload::make('hero_banner_path')
                            ->label('Latar Seksi 1: Beranda / Hero')
                            ->disk('public')
                            ->directory('landing/parallax')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Gambar latar utama beranda atas (default: interior gereja katedral).'),

                        FileUpload::make('warta_bg_path')
                            ->label('Latar Seksi 2: Warta Jemaat & Sabda Firman')
                            ->disk('public')
                            ->directory('landing/parallax')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Gambar latar warta & renungan (default: Alkitab terbuka di meja kayu).'),

                        FileUpload::make('branches_bg_path')
                            ->label('Latar Seksi 3: Pos Pelayanan & Kelompok Jemaat')
                            ->disk('public')
                            ->directory('landing/parallax')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Gambar latar pos pelayanan gereja (default: bangunan gereja Kristen / kapel).'),

                        FileUpload::make('worship_bg_path')
                            ->label('Latar Seksi 4: Jadwal Ibadah Raya')
                            ->disk('public')
                            ->directory('landing/parallax')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Gambar latar jadwal liturgi (default: lilin & altar doa gereja).'),

                        FileUpload::make('profile_bg_path')
                            ->label('Latar Seksi 5: Profil & Rumah Bersama')
                            ->disk('public')
                            ->directory('landing/parallax')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Gambar latar eklesiologi Rumah Bersama (default: sanctuary katedral batu).'),

                        FileUpload::make('portal_bg_path')
                            ->label('Latar Seksi 6: Layanan Mandiri Jemaat & Majelis')
                            ->disk('public')
                            ->directory('landing/parallax')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Gambar latar pintu akses portal (default: kaca patri gereja / stained glass).'),
                    ])
                    ->columns(2),

                Section::make('4. Profil, Visi & Misi')
                    ->description('Ringkasan sejarah, visi, serta butir-butir misi pelayanan.')
                    ->schema([
                        Textarea::make('about_summary')
                            ->label('Ringkasan Sejarah & Profil')
                            ->rows(4)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('vision')
                            ->label('Visi Gereja')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),

                        Repeater::make('missions')
                            ->label('Daftar Butir Misi')
                            ->schema([
                                TextInput::make('item')
                                    ->label('Butir Misi')
                                    ->required()
                                    ->maxLength(500),
                            ])
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),

                Section::make('5. Jadwal Ibadah Induk')
                    ->description('Daftar jadwal ibadah rutin di gedung gereja utama.')
                    ->schema([
                        Repeater::make('worship_schedules')
                            ->label('Jadwal Ibadah')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama Ibadah')
                                    ->placeholder('Ibadah Raya Minggu Induk')
                                    ->required(),

                                TextInput::make('day')
                                    ->label('Hari')
                                    ->placeholder('Minggu')
                                    ->required(),

                                TextInput::make('time')
                                    ->label('Waktu')
                                    ->placeholder('08:30 WIB')
                                    ->required(),

                                TextInput::make('location')
                                    ->label('Lokasi')
                                    ->placeholder('Gedung Gereja Utama')
                                    ->required(),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('6. Kontak & Media Sosial')
                    ->description('Informasi sekretariat gereja induk dan saluran media sosial resmi.')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Email Sekretariat')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('contact_phone')
                            ->label('Nomor Telepon / WhatsApp')
                            ->tel()
                            ->maxLength(50),

                        Textarea::make('contact_address')
                            ->label('Alamat Lengkap Sekretariat')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('contact_maps_url')
                            ->label('URL Google Maps')
                            ->url()
                            ->columnSpanFull(),

                        KeyValue::make('social_links')
                            ->label('Tautan Media Sosial')
                            ->keyLabel('Platform (youtube, facebook, instagram, whatsapp)')
                            ->valueLabel('URL / Link')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Status Aktif Landing Page')
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->disabled(! $isSuperAdmin);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403, 'Hanya Super Admin yang dapat memperbarui pengaturan CMS landing page pusat.');

        $state = $this->form->getState();

        // Convert missions back to simple array of strings
        if (isset($state['missions']) && is_array($state['missions'])) {
            $state['missions'] = array_values(array_filter(array_map(
                fn ($m) => is_array($m) ? ($m['item'] ?? '') : (string) $m,
                $state['missions']
            )));
        }

        $setting = LandingSetting::current(null);
        $setting->update($state);

        Notification::make()
            ->title('Pengaturan CMS Berhasil Disimpan')
            ->body('Halaman depan resmi gereja telah diperbarui.')
            ->success()
            ->send();
    }
}
