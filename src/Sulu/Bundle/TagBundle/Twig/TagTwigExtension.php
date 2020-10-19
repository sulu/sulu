<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TagTwigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_tags', [TagRuntime::class, 'getTagsFunction']),
            new TwigFunction('sulu_tag_url', [TagRuntime::class, 'setTagUrlFunction']),
            new TwigFunction('sulu_tag_url_append', [TagRuntime::class, 'appendTagUrlFunction']),
            new TwigFunction('sulu_tag_url_clear', [TagRuntime::class, 'clearTagUrlFunction']),
        ];
    }
}
