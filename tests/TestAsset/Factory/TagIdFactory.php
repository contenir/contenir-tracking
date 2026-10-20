<?php

declare(strict_types=1);

namespace Contenir\Tracking\Tests\TestAsset\Factory;

use Contenir\Tracking\Provider;
use Contenir\Tracking\TagId;

/**
 * Valid tag IDs for tests.
 */
final class TagIdFactory
{
    public static function google(string $id = SettingsFactory::GOOGLE_TAG): TagId
    {
        return TagId::parse(Provider::GoogleTag, $id);
    }

    public static function linkedIn(string $id = SettingsFactory::LINKEDIN): TagId
    {
        return TagId::parse(Provider::LinkedInInsight, $id);
    }

    public static function meta(string $id = SettingsFactory::META_PIXEL): TagId
    {
        return TagId::parse(Provider::MetaPixel, $id);
    }

    public static function tagManager(string $id = SettingsFactory::TAG_MANAGER): TagId
    {
        return TagId::parse(Provider::TagManager, $id);
    }
}
