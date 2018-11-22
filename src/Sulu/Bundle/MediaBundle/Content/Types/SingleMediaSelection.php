<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content\Types;

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleMediaSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $mediaReferenceStore;

    public function __construct(MediaManagerInterface $mediaManager, ReferenceStoreInterface $referenceStore)
    {
        $this->mediaManager = $mediaManager;
        $this->mediaReferenceStore = $referenceStore;

        parent::__construct('SingleMediaSelection');
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property): ?Media
    {
        $data = $property->getValue();
        if (!isset($data['id'])) {
            return null;
        }

        return $this->mediaManager->getById($data['id'], $property->getStructure()->getLanguageCode());
    }

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $data = $property->getValue();
        if (!isset($data['id'])) {
            return;
        }

        $this->mediaReferenceStore->add($data['id']);
    }

    /**
     * {@inheritdoc}
     */
    protected function encodeValue($value)
    {
        return json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    protected function decodeValue($value)
    {
        if (!is_string($value)) {
            return null;
        }

        return json_decode($value, true);
    }
}
