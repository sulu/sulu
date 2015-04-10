<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\TypeManager;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\Entity\MediaType;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaTypeNotFoundException;

class DefaultTypeManager implements TypeManagerInterface
{

    /**
     * @var array
     */
    private $blockedMimeTypes;

    /**
     * @var array
     */
    private $mediaTypes;

    /**
     * @var MediaType[]
     */
    private $mediaTypeEntities;

    /**
     * @param EntityManager $em
     * @param array $mediaTypes
     * @param array $blockedMimeTypes
     */
    public function __construct(
        EntityManager $em,
        $mediaTypes,
        $blockedMimeTypes
    ) {
        $this->em = $em;
        $this->mediaTypes = $mediaTypes;
        $this->blockedMimeTypes = $blockedMimeTypes;
    }

    /**
     * @param int $id
     * @return MediaType
     * @throws MediaTypeNotFoundException
     */
    public function get($id)
    {
        /** @var MediaType $type */
        $type = $this->em->getRepository(self::ENTITY_NAME_MEDIATYPE)->find($id);
        if (!$type) {
            throw new MediaTypeNotFoundException('Collection Type with the ID ' . $id . ' not found');
        }

        return $type;
    }

    /**
     * @param string $fileMimeType
     * @return integer
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
            $mediaType = $this->em->getRepository(self::ENTITY_CLASS_MEDIATYPE)->findOneByName($name);
            $this->mediaTypeEntities[$name] = $mediaType;
        }

        return $this->mediaTypeEntities[$name]->getId();
    }
}
