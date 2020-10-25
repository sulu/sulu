<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to handle medias in frontend.
 */
class MediaTwigExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_resolve_media', [MediaRuntime::class, 'resolveMediaFunction']),
            new TwigFunction('sulu_resolve_medias', [MediaRuntime::class, 'resolveMediasFunction']),
        ];
    }
}
