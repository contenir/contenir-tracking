<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\Unit\Consent;

use Contenir\Tracking\Consent\Choice;
use Contenir\Tracking\Consent\ConsentEvent;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConsentEventTest extends TestCase
{
    #[Test]
    public function firesTheEventWithAChoice(): void
    {
        static::assertSame(
            'window.dispatchEvent(new CustomEvent("contenir:consent", {detail: {"analytics":true,"ads":false}}));',
            ConsentEvent::dispatch(new Choice(analytics: true)),
        );
    }

    #[Test]
    public function refusesEverythingByDefault(): void
    {
        $choice = new Choice();

        static::assertSame([false, false], [$choice->analytics, $choice->ads]);
    }
}
