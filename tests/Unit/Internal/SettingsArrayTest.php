<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Internal;

use Contenir\Tracking\Internal\SettingsArray;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SettingsArrayTest extends TestCase
{
    /**
     * @return array<string, array{mixed, bool, bool}>
     */
    public static function flags(): array
    {
        return [
            'true'             => [true, false, true],
            'false'            => [false, true, false],
            'one'              => ['1', false, true],
            'the integer one'  => [1, false, true],
            'zero'             => ['0', true, false],
            'the integer zero' => [0, true, false],
            'yes, padded'      => [' Yes ', false, true],
            'on'               => ['on', false, true],
            'true as text'     => ['TRUE', false, true],
            'no'               => ['no', true, false],
            'off'              => ['off', true, false],
            'false as text'    => ['false', true, false],
            'blank'            => ['', true, false],
            'unreadable, on'   => ['maybe', true, true],
            'unreadable, off'  => ['maybe', false, false],
            'the integer two'  => [2, false, false],
            'null, on'         => [null, true, true],
            'a list'           => [['1'], false, false],
            'a float'          => [1.0, false, false],
        ];
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function texts(): array
    {
        return [
            'text'                 => ['G-ABC12345', 'G-ABC12345'],
            'a number'             => [1_234_567, '1234567'],
            'null'                 => [null, ''],
            'a switch'             => [true, ''],
            'a float'              => [1.5, ''],
            'a list'               => [['ES', 'FR'], 'ES,FR'],
            'a list with non-text' => [['ES', 3, '', 'FR'], 'ES,FR'],
        ];
    }

    #[Test]
    public function findsANestedValueByDottedKey(): void
    {
        $settings = new SettingsArray(['google' => ['analytics' => ['key' => 'G-ABC12345']]]);

        static::assertSame('G-ABC12345', $settings->text('google.analytics.key'));
    }

    #[Test]
    public function findsNothingBelowAValue(): void
    {
        $settings = new SettingsArray(['google' => 'G-ABC12345']);

        static::assertSame('', $settings->text('google.analytics.key'));
    }

    #[Test]
    public function findsNothingForAMissingKey(): void
    {
        $settings = new SettingsArray(['google' => ['analytics' => []]]);

        static::assertTrue($settings->flag('google.analytics.key', default: true));
    }

    #[Test]
    #[DataProvider('flags')]
    public function readsASwitch(mixed $value, bool $default, bool $expected): void
    {
        static::assertSame($expected, (new SettingsArray(['a' => ['b' => $value]]))->flag('a.b', $default));
    }

    #[Test]
    #[DataProvider('texts')]
    public function readsText(mixed $value, string $expected): void
    {
        static::assertSame($expected, (new SettingsArray(['a' => $value]))->text('a'));
    }
}
