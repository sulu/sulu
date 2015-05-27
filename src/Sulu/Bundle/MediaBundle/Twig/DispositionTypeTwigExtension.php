<?php
/*
 * This file is part of the Sulu CMS.
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
        return array(
            new \Twig_SimpleFunction('get_media_url', array($this, 'getMediaUrl')),
        );
    }

    public function getMediaUrl(Media $media, $dispositionType = null)
    {
        if (!$dispositionType &&
            !($dispositionType = $this->getDispositionTypeByMimeType($media->getMimeType()))
        ) {
            $dispositionType = $this->default;
        }

        return $media->getUrl() . ($dispositionType === ResponseHeaderBag::DISPOSITION_INLINE ? '&inline=1' : '');
    }

    private function getDispositionTypeByMimeType($mimeType)
    {
        if (in_array($mimeType, $this->mimeTypesInline)) {
            return ResponseHeaderBag::DISPOSITION_INLINE;
        } elseif (in_array($mimeType, $this->mimeTypesAttachment)) {
            return ResponseHeaderBag::DISPOSITION_ATTACHMENT;
        }

        return null;
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
