<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat roster/jadwal pelayan berbentrok (satu orang dijadwalkan pada
 * dua acara yang waktunya tumpang tindih, atau duplikat pada acara yang sama).
 */
class RosterConflictException extends RuntimeException
{
    /**
     * @param  array<int, string>  $conflicts  Ringkasan bentrok yang terbaca manusia.
     */
    public static function forConflicts(array $conflicts): self
    {
        return new self('Jadwal petugas berbentrok: '.implode('; ', $conflicts));
    }
}
