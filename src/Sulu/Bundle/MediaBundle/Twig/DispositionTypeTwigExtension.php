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

use Sulu\Bundle\MediaBundle\Api\Media;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Extension for content form generation.
 */
class DispositionTypeTwigExtension extends \Twig_Extension
{
    /**
     * Returns an array of possible function in this extension.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_get_media_url', [$this, 'getMediaUrl']),
        ];
    }

    /**
     * Get media url.
     *
     * @param Media $media
     * @param null|string $dispositionType
     *
     * @return string
     */
    public function getMediaUrl(Media $media, $dispositionType = null)
    {
        $url = $media->getUrl();

        if (ResponseHeaderBag::DISPOSITION_INLINE === $dispositionType) {
            $url .= (false === strpos($url, '?') ? '?inline=1' : '&inline=1');
        } elseif (ResponseHeaderBag::DISPOSITION_ATTACHMENT === $dispositionType) {
            $url .= (false === strpos($url, '?') ? '?inline=0' : '&inline=0');
        }

        return $url;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'media_disposition_type';
    }
}
