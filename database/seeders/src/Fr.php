<?php

declare(strict_types=1);

namespace SuiteCrmCore\Seed;

/**
 * French helpers for demo data: fictional phone numbers, e-mail addresses,
 * SIRET, dates. Pure functions: the same input always gives the same output,
 * so demo data stays identical from one run to the next.
 */
final class Fr
{
    /**
     * Numbers reserved by ARCEP for fiction (films, books, demos): they
     * belong to nobody. Landline 01 99 00 xx xx, mobile 06 39 98 xx xx.
     */
    private const LANDLINE = '01 99 00';
    private const MOBILE = '06 39 98';

    /** Domain reserved for examples (RFC 2606): no mail ever leaves for it. */
    private const DOMAIN = 'example';

    /** A fictional phone number, e.g. phone(7) = "01 99 00 00 07". */
    public static function phone(int $number, bool $mobile = false): string
    {
        $digits = sprintf('%04d', $number % 10000);

        return ($mobile ? self::MOBILE : self::LANDLINE) . ' ' . substr($digits, 0, 2) . ' ' . substr($digits, 2, 2);
    }

    /** Lowercase ASCII, words joined by a dash: "Bâtiments Durand" → "batiments-durand". */
    public static function slug(string $text): string
    {
        $ascii = strtr($text, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ÿ' => 'y',
            'À' => 'a', 'Â' => 'a', 'Ç' => 'c', 'É' => 'e', 'È' => 'e', 'Ê' => 'e', 'Î' => 'i', 'Ô' => 'o',
            'Ù' => 'u', 'Û' => 'u', 'œ' => 'oe', 'Œ' => 'oe', 'æ' => 'ae', '&' => ' et ',
        ]);

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-');
    }

    /** Fictional e-mail address: "prenom.nom@entreprise.example". */
    public static function email(string $firstName, string $lastName, string $company): string
    {
        return self::slug($firstName) . '.' . self::slug($lastName) . '@' . self::slug($company) . '.' . self::DOMAIN;
    }

    /** Fictional website: "https://www.entreprise.example". */
    public static function website(string $company): string
    {
        return 'https://www.' . self::slug($company) . '.' . self::DOMAIN;
    }

    /**
     * A valid but fictional SIRET (14 digits, Luhn key), the same for the same
     * key. Starts with 9: no real SIREN starts with it today.
     */
    public static function siret(string $key): string
    {
        $digits = '9' . substr((string) hexdec(substr(sha1($key), 0, 12)), 0, 12);
        $digits = str_pad($digits, 13, '0');

        for ($check = 0; $check <= 9; ++$check) {
            if (self::luhn($digits . $check)) {
                return $digits . $check;
            }
        }

        throw new \LogicException('unreachable: one of the ten keys always passes Luhn');
    }

    /** Luhn check, used by SIREN and SIRET numbers. */
    public static function luhn(string $number): bool
    {
        if (!preg_match('/^\d+$/', $number)) {
            return false;
        }
        $sum = 0;
        foreach (array_reverse(str_split($number)) as $position => $digit) {
            $value = (int) $digit * ($position % 2 === 1 ? 2 : 1);
            $sum += $value > 9 ? $value - 9 : $value;
        }

        return $sum % 10 === 0;
    }

    /**
     * A date relative to today, in database format: date(30) is in 30 days,
     * date(-7) a week ago. Demo data then always looks current.
     */
    public static function date(int $days, ?\DateTimeImmutable $today = null): string
    {
        return ($today ?? new \DateTimeImmutable('today'))->modify(sprintf('%+d days', $days))->format('Y-m-d');
    }
}
