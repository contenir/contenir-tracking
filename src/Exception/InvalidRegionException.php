<?php

declare(strict_types=1);

namespace Contenir\Tracking\Exception;

use InvalidArgumentException;

/**
 * Thrown when a consent region isn't an ISO 3166 country or subdivision code.
 *
 * @api
 */
final class InvalidRegionException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
