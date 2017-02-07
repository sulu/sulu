<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var string
     */
    private $default;

    /**
     * @var array
     */
    private $mimeTypesInline;

    /**
     * @var array
     */
    private $mimeTypesAttachment;

    public function __construct($default, array $mimeTypesInline, array $mimeTypesAttachment)
    {
        $this->default = $default;
        $this->mimeTypesInline = $mimeTypesInline;
        $this->mimeTypesAttachment = $mimeTypesAttachment;
    }

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

    public function getMediaUrl(Media $media, $dispositionType = null)
    {
        if (!$dispositionType &&
            !($dispositionType = $this->getDispositionTypeByMimeType($media->getMimeType()))
        ) {
            $dispositionType = $this->default;
        }

        $url = $media->getUrl();

        if ($dispositionType === ResponseHeaderBag::DISPOSITION_INLINE) {
            $url .= (false === strpos($url, '?') ? '?inline=1' : '&inline=1');
        }

        return $url;
    }

    private function getDispositionTypeByMimeType($mimeType)
    {
        if (in_array($mimeType, $this->mimeTypesInline)) {
            return ResponseHeaderBag::DISPOSITION_INLINE;
        } elseif (in_array($mimeType, $this->mimeTypesAttachment)) {
            return ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        }

        return;
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
