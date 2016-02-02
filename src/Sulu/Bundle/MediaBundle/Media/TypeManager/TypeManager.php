<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\TypeManager;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaTypeNotFoundException;

/**
 * Class TypeManager
 * Default Type Manager used to get correct media type by a mime type.
 */
class TypeManager implements TypeManagerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var array
     */
    private $mediaTypes;

    /**
     * @var array
     */
    private $blockedMimeTypes;

    /**
     * @var MediaType[]
     */
    private $mediaTypeEntities;

    /**
     * @param ObjectManager $objectManager
     * @param array         $mediaTypes
     * @param array         $blockedMimeTypes
     */
    public function __construct(
        ObjectManager $objectManager,
        $mediaTypes,
        $blockedMimeTypes
    ) {
        $this->objectManager = $objectManager;
        $this->mediaTypes = $mediaTypes;
        $this->blockedMimeTypes = $blockedMimeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        /** @var MediaType $type */
        $type = $this->objectManager->getRepository(self::ENTITY_NAME_MEDIATYPE)->find($id);
        if (!$type) {
            throw new MediaTypeNotFoundException('Collection Type with the ID ' . $id . ' not found');
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediaType($fileMimeType)
    {
        $name = null;
        foreach ($this->mediaTypes as $mediaType) {
            foreach ($mediaType['mimeTypes'] as $mimeType) {
                if (fnmatch($mimeType, $fileMimeType)) {
                    $name = $mediaType['type'];
                }
            }
        }

        if (!isset($this->mediaTypeEntities[$name])) {
            $mediaType = $this->objectManager->getRepository(self::ENTITY_CLASS_MEDIATYPE)->findOneByName($name);
            $this->mediaTypeEntities[$name] = $mediaType;
        }

        return $this->mediaTypeEntities[$name]->getId();
    }
}
