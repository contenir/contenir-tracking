<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Consent;

use Contenir\Tracking\Consent\ConsentSettings;
use Contenir\Tracking\Consent\Regions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConsentSettingsTest extends TestCase
{
    #[Test]
    public function keepsTheRegionsGiven(): void
    {
        $regions = Regions::parse('ES');

        static::assertSame($regions, (new ConsentSettings(regions: $regions))->regions);
    }

    #[Test]
    public function waitsEverywhereWithAdSignalsAndNoPassthroughByDefault(): void
    {
        $settings = new ConsentSettings();

        static::assertSame(
            [true, [], true, false],
            [$settings->waitForConsent, $settings->regions->codes, $settings->adSignals, $settings->urlPassthrough],
        );
    }
}
