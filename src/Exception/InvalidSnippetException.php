<?php

declare(strict_types=1);

namespace Contenir\Tracking\Exception;

use InvalidArgumentException;

/**
 * Thrown when a snippet couldn't be written safely: no code or src, or inline
 * code that would close its own script element.
 *
 * @api
 */
final class InvalidSnippetException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
