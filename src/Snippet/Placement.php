<?php

declare(strict_types=1);

namespace Contenir\Tracking\Snippet;

/**
 * Where on a page a snippet goes. The values match contenir/extension-api's
 * Script\Placement, so an add-on can pass them straight through.
 *
 * @api
 */
enum Placement: string
{
    case BodyEnd   = 'body-end';
    case BodyStart = 'body-start';
    case Head      = 'head';
}
