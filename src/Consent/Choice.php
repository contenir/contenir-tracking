<?php

declare(strict_types=1);

namespace Contenir\Tracking\Consent;

/**
 * What a visitor allowed, as a contenir:consent event carries it: analytics,
 * and ads (advertising and remarketing).
 *
 * @api
 */
final readonly class Choice
{
    public function __construct(
        public bool $analytics = false,
        public bool $ads = false,
    ) {}
}
