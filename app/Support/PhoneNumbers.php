<?php

namespace App\Support;

/**
 * Phone numbers, stored one way: E.164.
 *
 * "+2348031234567" — plus, country code, national number, no spaces. A number
 * typed as 0803… in Lagos and the same number typed as +234 803… from London
 * are the same person, and only one of those spellings can be the one we keep.
 *
 * The country list itself is config/phone.php.
 */
final class PhoneNumbers
{
    /** A plus, a country code that cannot start with zero, then 6–14 more digits. */
    private const E164 = '/^\+[1-9]\d{6,14}$/';

    /**
     * Every country the picker offers, the ones we serve first.
     *
     * @return array<int, array{iso: string, name: string, dial: string, preferred: bool}>
     */
    public static function countries(): array
    {
        $preferred = (array) config('phone.preferred', []);

        $all = collect(config('phone.countries', []))
            ->map(fn (array $row) => [
                'iso'       => $row[0],
                'name'      => $row[1],
                'dial'      => $row[2],
                'preferred' => in_array($row[0], $preferred, true),
            ]);

        // Pinned first, in the order they are pinned; everything else as listed.
        return $all->sortBy(fn ($country) => $country['preferred']
                ? array_search($country['iso'], $preferred, true)
                : 1000)
            ->values()
            ->all();
    }

    public static function dialFor(?string $iso): ?string
    {
        foreach (config('phone.countries', []) as [$code, $name, $dial]) {
            if (strtoupper((string) $iso) === $code) {
                return $dial;
            }
        }

        return null;
    }

    /**
     * Which flag the picker opens on.
     *
     * Whatever the visitor's address says, when we know a country for it and
     * offer that country — otherwise the configured default. Being wrong here
     * is cheap; the picker is one tap away.
     */
    public static function defaultCountry(?string $detected = null): string
    {
        $detected = strtoupper((string) $detected);

        if ($detected !== '' && self::dialFor($detected) !== null) {
            return $detected;
        }

        return strtoupper((string) config('phone.default', 'NG'));
    }

    /**
     * Join a dialling code and a typed number into one stored number.
     *
     * The leading zero of a national number is dropped — 0803… is how the
     * number is written at home, but it is not part of it internationally.
     */
    public static function e164(?string $dial, ?string $national): ?string
    {
        $dial     = preg_replace('/\D/', '', (string) $dial);
        $national = preg_replace('/\D/', '', (string) $national);

        if ($dial === '' || $national === '') {
            return null;
        }

        $national = ltrim($national, '0');

        if ($national === '') {
            return null;
        }

        return '+' . $dial . $national;
    }

    public static function isValid(?string $value): bool
    {
        return is_string($value) && preg_match(self::E164, $value) === 1;
    }

    /**
     * The validation rules for a phone field.
     *
     * @return array<int, mixed>
     */
    public static function rules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
            'regex:' . self::E164,
        ];
    }

    /** Said the way a person would read it back — "+234 803 123 4567". */
    public static function pretty(?string $e164): string
    {
        if (! self::isValid($e164)) {
            return (string) $e164;
        }

        $digits = substr((string) $e164, 1);

        foreach (config('phone.countries', []) as [$iso, $name, $dial]) {
            if (str_starts_with($digits, $dial)) {
                $rest = substr($digits, strlen($dial));

                return '+' . $dial . ' ' . trim(chunk_split($rest, 3, ' '));
            }
        }

        return (string) $e164;
    }
}
