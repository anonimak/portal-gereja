@props([
    'church' => null,
    'churchName' => null,
    'synod' => null,
    'churchAddress' => null,
    'churchPhone' => null,
    'churchEmail' => null,
    'logoBase64' => null,
    'title' => null,
    'subTitle' => null,
    'periodLabel' => null,
    'documentNumber' => null,
])

@php
    // Fallback resolver jika objek $church dilewatkan
    $name = $churchName ?? $church?->name ?? 'PORTAL GEREJA';
    $synodText = $synod ?? $church?->synod ?? null;
    $address = $churchAddress ?? $church?->address ?? null;
    $phone = $churchPhone ?? $church?->phone ?? null;
    $email = $churchEmail ?? $church?->email ?? null;
    $logo = $logoBase64 ?? $church?->logo_base64 ?? null;
@endphp

<div class="letterhead-container" style="width: 100%; margin-bottom: 18px; font-family: 'DejaVu Sans', sans-serif;">
    <table style="width: 100%; border-collapse: collapse; border: none;">
        <tr>
            @if ($logo)
                <td style="width: 75px; vertical-align: middle; text-align: center; padding-right: 12px; border: none;">
                    <img src="{{ $logo }}" alt="Logo" style="max-width: 70px; max-height: 70px; object-fit: contain;">
                </td>
            @endif
            <td style="vertical-align: middle; text-align: center; border: none;">
                @if ($synodText)
                    <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #4b5563; margin-bottom: 2px;">
                        {{ $synodText }}
                    </div>
                @endif
                <div style="font-size: 17px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #111827; line-height: 1.2;">
                    {{ $name }}
                </div>
                @if ($address)
                    <div style="font-size: 9.5px; color: #374151; margin-top: 3px; line-height: 1.3;">
                        {{ $address }}
                    </div>
                @endif
                @if ($phone || $email)
                    <div style="font-size: 8.5px; color: #6b7280; margin-top: 2px;">
                        @if ($phone) Telp: {{ $phone }} @endif
                        @if ($phone && $email) &bull; @endif
                        @if ($email) Email: {{ $email }} @endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Garis Pemisah Kop Surat Resmi: Garis Ganda Standar (Tebal 2px + Tipis 1px) --}}
    <div style="border-bottom: 2px solid #111827; margin-top: 8px;"></div>
    <div style="border-bottom: 1px solid #111827; margin-top: 2px;"></div>

    {{-- Judul Dokumen (Jika disediakan) --}}
    @if ($title)
        <div style="text-align: center; margin-top: 14px; margin-bottom: 4px;">
            <div style="font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #111827;">
                {{ $title }}
            </div>
            @if ($documentNumber)
                <div style="font-size: 10px; color: #4b5563; margin-top: 2px;">
                    Nomor: {{ $documentNumber }}
                </div>
            @endif
            @if ($periodLabel)
                <div style="font-size: 10px; font-weight: 600; color: #4b5563; margin-top: 2px;">
                    Periode: {{ $periodLabel }}
                </div>
            @endif
            @if ($subTitle)
                <div style="font-size: 9.5px; color: #6b7280; margin-top: 1px;">
                    {{ $subTitle }}
                </div>
            @endif
        </div>
    @endif
</div>
