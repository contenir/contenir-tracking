<?php

declare(strict_types=1);

namespace Contenir\Tracking\Snippet;

/**
 * What a noscript fallback writes.
 *
 * @api
 */
enum NoscriptKind: string
{
    case Frame = 'frame';
    case Pixel = 'pixel';
}
