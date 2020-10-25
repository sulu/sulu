<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Seo;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * This twig extension provides support for the SEO functionality provided by Sulu.
 */
class SeoTwigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_seo', [$this, 'renderSeoTags'], ['needs_environment' => true]),
        ];
    }
}
