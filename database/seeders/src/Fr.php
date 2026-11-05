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

    /**
     * Real French towns with their postcode, for generated addresses: an
     * address stays consistent (the postcode is the town's).
     *
     * @var non-empty-list<array{string, string}>
     */
    public const TOWNS = [
        ['75011', 'Paris'], ['13001', 'Marseille'], ['69002', 'Lyon'], ['31000', 'Toulouse'], ['06000', 'Nice'],
        ['44000', 'Nantes'], ['34000', 'Montpellier'], ['67000', 'Strasbourg'], ['33000', 'Bordeaux'], ['59000', 'Lille'],
        ['35000', 'Rennes'], ['51100', 'Reims'], ['42000', 'Saint-Étienne'], ['83000', 'Toulon'], ['38000', 'Grenoble'],
        ['21000', 'Dijon'], ['49000', 'Angers'], ['30000', 'Nîmes'], ['63000', 'Clermont-Ferrand'], ['72000', 'Le Mans'],
        ['29200', 'Brest'], ['37000', 'Tours'], ['80000', 'Amiens'], ['87000', 'Limoges'], ['74000', 'Annecy'],
        ['66000', 'Perpignan'], ['57000', 'Metz'], ['25000', 'Besançon'], ['45000', 'Orléans'], ['68100', 'Mulhouse'],
        ['76000', 'Rouen'], ['14000', 'Caen'], ['54000', 'Nancy'], ['64000', 'Pau'], ['17000', 'La Rochelle'],
        ['86000', 'Poitiers'], ['84000', 'Avignon'], ['56000', 'Vannes'], ['73000', 'Chambéry'], ['26000', 'Valence'],
    ];

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

    /** Legal forms left out of domain names, as real companies do. */
    private const LEGAL_FORMS = '/\s+(S\.?A\.?R\.?L\.?|S\.?A\.?S\.?U?|E\.?U\.?R\.?L\.?|S\.?A\.?|S\.?N\.?C\.?)$/u';

    /** Fictional e-mail address: "prenom.nom@entreprise.example". */
    public static function email(string $firstName, string $lastName, string $company): string
    {
        return self::slug($firstName) . '.' . self::slug($lastName) . '@' . self::domain($company);
    }

    /** Fictional website: "https://www.entreprise.example". */
    public static function website(string $company): string
    {
        return 'https://www.' . self::domain($company);
    }

    /** Domain of a company, without its legal form: "Martin Conseil SARL" → "martin-conseil.example". */
    public static function domain(string $company): string
    {
        return self::slug((string) preg_replace(self::LEGAL_FORMS, '', $company)) . '.' . self::DOMAIN;
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
