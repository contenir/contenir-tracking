<?php

declare(strict_types=1);

namespace Contenir\Tracking\Internal;

use function array_filter;
use function array_key_exists;
use function array_map;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function strtolower;
use function trim;

/**
 * Reads values from a site's settings, held as nested arrays, by dotted key.
 *
 * @internal
 */
final readonly class SettingsArray
{
    /**
     * Values that switch a setting on, as settings files store them.
     */
    private const array ON = ['1', 'true', 'on', 'yes'];

    /**
     * Values that switch a setting off.
     */
    private const array OFF = ['0', 'false', 'off', 'no', ''];

    /**
     * @param array<array-key, mixed> $settings
     */
    public function __construct(
        private array $settings,
    ) {}

    /**
     * The value at $key as a switch, or $default when it is missing or
     * can't be read as one.
     *
     * @mago-expect analysis:mixed-assignment Settings are untyped; the value is checked here.
     */
    public function flag(string $key, bool $default): bool
    {
        $value = $this->find($key);
        if (is_bool($value)) {
            return $value;
        }

        $text = is_string($value) || is_int($value) ? strtolower(trim((string) $value)) : null;

        return match (true) {
            in_array($text, self::ON, strict: true) => true,
            in_array($text, self::OFF, strict: true) => false,
            default => $default,
        };
    }

    /**
     * The value at $key as text; a list is joined with commas, and anything
     * else is an empty string.
     *
     * @mago-expect analysis:mixed-assignment Settings are untyped; the value is checked here.
     */
    public function text(string $key): string
    {
        $value = $this->find($key);
        if (is_array($value)) {
            return implode(
                ',',
                array_filter(array_map(
                    static fn(mixed $part): string => is_string($part) ? $part : '',
                    $value,
                )),
            );
        }

        return is_string($value) || is_int($value) ? (string) $value : '';
    }

    /**
     * @mago-expect analysis:mixed-assignment Settings are untyped; each level is checked here.
     */
    private function find(string $key): mixed
    {
        $value = $this->settings;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
