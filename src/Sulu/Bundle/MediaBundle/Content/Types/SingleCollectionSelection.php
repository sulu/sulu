<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content\Types;

use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleCollectionSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var CollectionManagerInterface
     */
    protected $collectionManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $collectionReferenceStore;

    public function __construct(
        CollectionManagerInterface $collectionManager,
        ReferenceStoreInterface $collectionReferenceStore
    ) {
        $this->collectionManager = $collectionManager;
        $this->collectionReferenceStore = $collectionReferenceStore;

        parent::__construct('SingleCollectionSelection');
    }

    public function getContentData(PropertyInterface $property): ?Collection
    {
        $id = $property->getValue();
        $locale = $property->getStructure()->getLanguageCode();

        if (!$id) {
            return null;
        }

        try {
            return $this->collectionManager->getById($id, $locale);
        } catch (CollectionNotFoundException $e) {
            return null;
        }
    }

    public function preResolve(PropertyInterface $property): void
    {
        $id = $property->getValue();
        if (!$id) {
            return;
        }

        $this->collectionReferenceStore->add($id);
    }
}
