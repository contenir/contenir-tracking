<?php

declare(strict_types=1);

namespace Contenir\Tracking;

use Contenir\Tracking\Exception\InvalidTagIdException;
use Override;
use Stringable;

use function sprintf;
use function str_starts_with;

/**
 * A provider's tag ID that has been checked: G-XXXXXXXXXX, GTM-XXXXXXX, a Meta
 * pixel number or a LinkedIn partner number.
 *
 * @api
 */
final readonly class TagId implements Stringable
{
    private function __construct(
        public Provider $provider,
        public string $value,
    ) {}

    /**
     * @throws InvalidTagIdException When $value is blank or not the provider's shape.
     */
    public static function parse(Provider $provider, string $value): self
    {
        $id = $provider->normalise($value);
        if ('' === $id) {
            throw InvalidTagIdException::because(sprintf('A %s is needed', $provider->label()));
        }

        $problem = $provider->problem($id);
        if (null !== $problem) {
            throw InvalidTagIdException::because($problem);
        }

        return new self($provider, $id);
    }

    /**
     * The ID, or null when $value is blank or not the provider's shape.
     */
    public static function tryParse(Provider $provider, string $value): ?self
    {
        $id = $provider->normalise($value);
        if ('' === $id || null !== $provider->problem($id)) {
            return null;
        }

        return new self($provider, $id);
    }

    /**
     * Whether the ID is a Google Ads conversion ID (AW-), which is
     * advertising rather than analytics.
     */
    public function isAds(): bool
    {
        return Provider::GoogleTag === $this->provider && str_starts_with($this->value, 'AW-');
    }

    #[Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
