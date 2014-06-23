<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\RestObject;

/**
 * helper functions for rest objects
 * @package Sulu\Bundle\MediaBundle\Media\RestObject
 */
class RestObjectHelper
{
    /**
     * convert media entities array to flat media rest object array
     * @param \Sulu\Bundle\MediaBundle\Entity\Media[] $mediaList
     * @param string $locale
     * @param string[] $fields
     * @return Media[]
     */
    public static function convertMediasToRestObjects($mediaList, $locale, $fields = array())
    {
        $flatMediaList = array();

        foreach ($mediaList as $media) {
            $flatMediaList[] = self::convertMediaToRestObject($media, $locale, $fields);
        }

        return $flatMediaList;
    }

    /**
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     * @param string $locale
     * @param string[] $fields
     * @return Media
     */
    public static function convertMediaToRestObject($media, $locale, $fields = array())
    {
        $flatMedia = new Media();

        if ($media instanceof \Sulu\Bundle\MediaBundle\Entity\Media) {
            $flatMedia->setDataByEntity($media, $locale);
        } else {
            $flatMedia->setDataByEntityArray($media, $locale);
        }

        return $flatMedia->toArray($fields);
    }
} 
