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
use Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface;

/**
 * helper functions for rest objects
 * @package Sulu\Bundle\MediaBundle\Media\RestObject
 */
class RestObjectHelper
{

    /**
     * @var \Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface
     */
    protected $formatManager;

    /**
     * @param FormatManagerInterface $formatManager
     */
    public function __construct(FormatManagerInterface $formatManager)
    {
        $this->formatManager = $formatManager;
    }


    /**
     * convert media entities array to flat media rest object array
     * @param \Sulu\Bundle\MediaBundle\Entity\Media[] $mediaList
     * @param string $locale
     * @param string[] $fields
     * @return Media[]
     */
    public function convertMediasToRestObjects($mediaList, $locale, $fields = array())
    {
        $flatMediaList = array();

        foreach ($mediaList as $media) {
            $flatMediaList[] = $this->convertMediaToRestObject($media, $locale, $fields);
        }

        return $flatMediaList;
    }

    /**
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     * @param string $locale
     * @param string[] $fields
     * @return array
     */
    public function convertMediaToRestObject($media, $locale, $fields = array())
    {
        $flatMedia = new Media();

        if ($media instanceof \Sulu\Bundle\MediaBundle\Entity\Media) {
            $flatMedia->setDataByEntity($media, $locale);
        } else {
            $flatMedia->setDataByEntityArray($media, $locale);
        }
        $flatMedia->setFormats($this->getFormats($flatMedia->getId(), $flatMedia->getName(), $flatMedia->getStorageOptions()));

        return $flatMedia->toArray($fields);
    }

    /**
     * @param $id
     * @param $name
     * @param $storageOptions
     * @return mixed
     */
    public function getFormats($id, $name, $storageOptions)
    {
        return $this->formatManager->getFormats($id, $name, $storageOptions);
    }

    /**
     * @param $id
     * @param $version
     * @param $storageOptions
     * @return mixed
     */
    public function getUrl($id, $version, $storageOptions)
    {
        return $this->formatManager->getOriginal($id, $version, $storageOptions);
    }
} 
