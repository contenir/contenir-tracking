<?php

declare(strict_types=1);

namespace Contenir\Tracking\Consent;

use Contenir\Tracking\Exception\InvalidRegionException;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function ctype_alnum;
use function ctype_upper;
use function implode;
use function is_string;
use function preg_split;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;
use function trim;

/**
 * The regions where tags wait for consent, as Google's Consent Mode names
 * them: ISO 3166-1 country codes (ES, GB) or ISO 3166-2 subdivisions (US-CA).
 * Editors enter them separated by commas, spaces or new lines.
 *
 * @api
 */
final readonly class Regions
{
    private const int COUNTRY = 2;

    private const int MAX_SUBDIVISION = 3;

    /**
     * @param list<string> $codes
     */
    private function __construct(
        public array $codes,
    ) {}

    /**
     * No regions: tags wait for consent everywhere.
     */
    public static function everywhere(): self
    {
        return new self([]);
    }

    /**
     * @param string|list<string> $value
     *
     * @throws InvalidRegionException When a code isn't a country or subdivision code.
     */
    public static function parse(string|array $value): self
    {
        $problems = self::problems($value);
        if ([] !== $problems) {
            throw InvalidRegionException::because(implode(' ', $problems));
        }

        return new self(self::split($value));
    }

    /**
     * What is wrong with $value, one message a code; empty when every code
     * is valid.
     *
     * @param string|list<string> $value
     *
     * @return list<string>
     */
    public static function problems(string|array $value): array
    {
        return array_values(array_map(
            static fn(string $code): string => sprintf(
                '"%s" is not a region code. Use a country code such as ES or a subdivision such as US-CA.',
                $code,
            ),
            array_filter(self::split($value), static fn(string $code): bool => ! self::valid($code)),
        ));
    }

    /**
     * @param string|list<string> $value
     *
     * @return list<string>
     */
    private static function split(string|array $value): array
    {
        $split = is_string($value) ? preg_split('/[\s,;]+/', $value) : $value;
        $parts = false === $split ? [] : $split;

        return array_values(array_unique(array_filter(
            array_map(static fn(string $code): string => strtoupper(trim($code)), $parts),
            static fn(string $code): bool => '' !== $code,
        )));
    }

    private static function valid(string $code): bool
    {
        $country = substr($code, offset: 0, length: self::COUNTRY);
        if (self::COUNTRY !== strlen($country) || ! ctype_upper($country)) {
            return false;
        }

        if (self::COUNTRY === strlen($code)) {
            return true;
        }

        $subdivision = substr($code, offset: self::COUNTRY + 1);
        $length      = strlen($subdivision);

        return '-' === $code[self::COUNTRY]
            && $length >= 1
            && $length <= self::MAX_SUBDIVISION
            && ctype_alnum($subdivision);
    }

    /**
     * Whether the list is empty, so tags wait for consent everywhere.
     */
    public function isEverywhere(): bool
    {
        return [] === $this->codes;
    }
}
