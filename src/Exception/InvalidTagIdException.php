<?php

declare(strict_types=1);

namespace Contenir\Tracking\Exception;

use InvalidArgumentException;

/**
 * Thrown when a tag ID doesn't have its provider's shape.
 *
 * @api
 */
final class InvalidTagIdException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
