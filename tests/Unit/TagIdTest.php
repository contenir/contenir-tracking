<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit;

use Contenir\Tracking\Exception\InvalidTagIdException;
use Contenir\Tracking\Provider;
use Contenir\Tracking\TagId;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class TagIdTest extends TestCase
{
    #[Test]
    public function keepsTheNormalisedId(): void
    {
        $id = TagId::parse(Provider::GoogleTag, ' g-abc12345 ');

        static::assertSame([Provider::GoogleTag, 'G-ABC12345', 'G-ABC12345'], [
            $id->provider,
            $id->value,
            (string) $id,
        ]);
    }

    #[Test]
    public function knowsAGoogleAdsId(): void
    {
        static::assertTrue(TagId::parse(Provider::GoogleTag, 'AW-123456789')->isAds());
    }

    #[Test]
    public function knowsAMeasurementIdIsNotAds(): void
    {
        static::assertFalse(TagId::parse(Provider::GoogleTag, 'G-ABC12345')->isAds());
    }

    #[Test]
    public function knowsANumberStartingAwIsOnlyAdsForGoogle(): void
    {
        static::assertFalse(TagId::parse(Provider::TagManager, 'GTM-AW12345')->isAds());
    }

    #[Test]
    public function refusesABlankId(): void
    {
        $this->expectExceptionObject(InvalidTagIdException::because('A Meta pixel ID is needed'));

        TagId::parse(Provider::MetaPixel, '  ');
    }

    #[Test]
    public function refusesAnIdOfTheWrongShapeWithTheProvidersMessage(): void
    {
        $this->expectExceptionObject(
            InvalidTagIdException::because('Enter a Tag Manager container ID such as GTM-XXXXXXX.'),
        );

        TagId::parse(Provider::TagManager, 'G-ABC12345');
    }

    #[Test]
    public function triesABlankIdAsNone(): void
    {
        static::assertNull(TagId::tryParse(Provider::LinkedInInsight, ''));
    }

    #[Test]
    public function triesAnIdOfTheWrongShapeAsNone(): void
    {
        static::assertNull(TagId::tryParse(Provider::LinkedInInsight, 'abc'));
    }

    #[Test]
    public function triesAValidId(): void
    {
        static::assertSame('1234567', TagId::tryParse(Provider::LinkedInInsight, ' 1234567 ')?->value);
    }
}
