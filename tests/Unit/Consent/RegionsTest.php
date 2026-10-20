<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Consent;

use Contenir\Tracking\Consent\Regions;
use Contenir\Tracking\Exception\InvalidRegionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RegionsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function invalidCodes(): array
    {
        return [
            'one letter'           => ['E'],
            'three letters'        => ['ESP'],
            'digits'               => ['12'],
            'a letter and a digit' => ['E1'],
            'no subdivision'       => ['US-'],
            'long subdivision'     => ['US-CALI'],
            'wrong separator'      => ['US_CA'],
            'punctuation'          => ['US-C.'],
            'accented'             => ['ÉS'],
        ];
    }

    /**
     * @return array<string, array{string|list<string>, list<string>}>
     */
    public static function lists(): array
    {
        return [
            'commas'                     => ['ES,FR', ['ES', 'FR']],
            'spaces and new lines'       => ["ES FR\nUS-CA", ['ES', 'FR', 'US-CA']],
            'semicolons'                 => ['ES; FR', ['ES', 'FR']],
            'lower case'                 => ['es, us-ca', ['ES', 'US-CA']],
            'repeated'                   => ['ES, es', ['ES']],
            'blank'                      => [" ,\n", []],
            'a list'                     => [[' gb ', 'US-NY', ''], ['GB', 'US-NY']],
            'a one-letter subdivision'   => ['FR-A', ['FR-A']],
            'a digit subdivision'        => ['JP-13', ['JP-13']],
            'a three-letter subdivision' => ['GB-ENG', ['GB-ENG']],
        ];
    }

    #[Test]
    public function isEverywhereWithNoCodes(): void
    {
        static::assertTrue(Regions::everywhere()->isEverywhere());
    }

    #[Test]
    public function isNotEverywhereWithACode(): void
    {
        static::assertFalse(Regions::parse('ES')->isEverywhere());
    }

    /**
     * @param string|list<string> $value
     * @param list<string>        $codes
     */
    #[Test]
    #[DataProvider('lists')]
    public function readsCodesAsEditorsEnterThem(string|array $value, array $codes): void
    {
        static::assertSame($codes, Regions::parse($value)->codes);
    }

    #[Test]
    public function refusesAListWithABadCode(): void
    {
        $this->expectExceptionObject(InvalidRegionException::because(
            '"E" is not a region code. Use a country code such as ES or a subdivision such as US-CA.',
        ));

        Regions::parse('ES, E, FR');
    }

    #[Test]
    #[DataProvider('invalidCodes')]
    public function reportsACodeThatIsNotARegion(string $code): void
    {
        static::assertCount(1, Regions::problems("ES, {$code}"));
    }

    #[Test]
    public function reportsEachBadCodeOnce(): void
    {
        static::assertSame(
            [
                '"E" is not a region code. Use a country code such as ES or a subdivision such as US-CA.',
                '"F1" is not a region code. Use a country code such as ES or a subdivision such as US-CA.',
            ],
            Regions::problems('E, ES, F1, e'),
        );
    }
}
