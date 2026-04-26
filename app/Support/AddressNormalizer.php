<?php

namespace App\Support;

class AddressNormalizer
{
    /**
     * Common US street-suffix abbreviations Pint-style. Keys are full forms,
     * values are the canonical short form. Both keys and values are
     * recognized — normalize collapses everything to the value side.
     */
    private const SUFFIX_MAP = [
        'street' => 'st',
        'avenue' => 'ave',
        'road' => 'rd',
        'boulevard' => 'blvd',
        'drive' => 'dr',
        'lane' => 'ln',
        'place' => 'pl',
        'court' => 'ct',
        'circle' => 'cir',
        'terrace' => 'ter',
        'parkway' => 'pkwy',
        'highway' => 'hwy',
        'apartment' => 'apt',
    ];

    private const DIRECTION_MAP = [
        'north' => 'n',
        'south' => 's',
        'east' => 'e',
        'west' => 'w',
        'northeast' => 'ne',
        'northwest' => 'nw',
        'southeast' => 'se',
        'southwest' => 'sw',
    ];

    /**
     * Return a normalized representation of (street, city) suitable for
     * exact-match comparison.
     *
     *   ['street' => 'normalized_street_only',
     *    'city'   => 'normalized_city',
     *    'fingerprint' => 'street|city composite key']
     *
     * Both inputs may be null; outputs are empty strings in that case.
     */
    public static function normalize(?string $street, ?string $city): array
    {
        $normStreet = self::normalizeStreet($street);
        $normCity = self::normalizeCity($city);

        return [
            'street' => $normStreet,
            'city' => $normCity,
            'fingerprint' => $normStreet.'|'.$normCity,
        ];
    }

    /**
     * Pull (street_number, first_alphanumeric_token) for the secondary
     * "same number + same first word" pass that catches entries the suffix
     * normalizer missed (e.g. typos in the street name).
     *
     * Returns null when the street has no leading numeric component.
     */
    public static function streetNumberAndFirstWord(?string $street): ?array
    {
        if ($street === null || trim($street) === '') {
            return null;
        }

        $cleaned = self::stripPunctuation(strtolower($street));
        if (! preg_match('/^\s*(\d+)\s+(\S+)/', $cleaned, $m)) {
            return null;
        }

        $firstWord = $m[2];
        // Skip pure direction tokens — "123 N Main" should match "123 Main".
        if (isset(self::DIRECTION_MAP[$firstWord])
            || in_array($firstWord, self::DIRECTION_MAP, true)) {
            // Try the next token instead.
            if (! preg_match('/^\s*\d+\s+\S+\s+(\S+)/', $cleaned, $m2)) {
                return null;
            }
            $firstWord = $m2[1];
        }

        return [$m[1], $firstWord];
    }

    private static function normalizeStreet(?string $street): string
    {
        if ($street === null) {
            return '';
        }

        $s = strtolower(trim($street));
        $s = self::stripPunctuation($s);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        // Replace whole-word suffixes and directions. Order matters: longer
        // forms before shorter, and process the long key set first so we
        // collapse "north" → "n" before "northeast" matches "north" inside.
        // Sort by length descending to be safe.
        $replacements = self::DIRECTION_MAP + self::SUFFIX_MAP;
        uksort($replacements, fn ($a, $b) => strlen($b) - strlen($a));

        foreach ($replacements as $long => $short) {
            $s = preg_replace('/\b'.preg_quote($long, '/').'\b/', $short, $s) ?? $s;
        }

        return trim($s);
    }

    private static function normalizeCity(?string $city): string
    {
        if ($city === null) {
            return '';
        }

        $s = strtolower(trim($city));
        $s = self::stripPunctuation($s);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return trim($s);
    }

    private static function stripPunctuation(string $s): string
    {
        return str_replace(['.', ',', '#'], ['', '', ''], $s);
    }
}
