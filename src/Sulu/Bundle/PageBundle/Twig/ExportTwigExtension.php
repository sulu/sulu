<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extensions for the Webspace export.
 */
class ExportTwigExtension extends AbstractExtension
{
    /**
     * Returns an array of possible function in this extension.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_content_type_export_escape', [ExportRuntime::class, 'escapeXmlContent']),
            new TwigFunction('sulu_content_type_export_counter', [ExportManagerInterface::class, 'counter']),
        ];
    }
}
