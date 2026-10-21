<?php

declare(strict_types=1);

namespace Contenir\Tracking\Snippet;

/**
 * The consent a visitor gives before a snippet may run. Necessary snippets
 * always run; when a site gates scripts, the others are written inert until
 * the visitor agrees. The values match contenir/contenir-extension-api's
 * Script\ConsentCategory.
 *
 * @api
 */
enum Category: string
{
    case Analytics   = 'analytics';
    case Marketing   = 'marketing';
    case Necessary   = 'necessary';
    case Preferences = 'preferences';
}
