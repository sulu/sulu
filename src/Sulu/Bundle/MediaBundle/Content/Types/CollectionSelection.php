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

class CollectionSelection extends SimpleContentType implements PreResolvableContentTypeInterface
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

        parent::__construct('CollectionSelection');
    }

    /**
     * @return Collection[]
     */
    public function getContentData(PropertyInterface $property): array
    {
        $ids = $property->getValue();
        $locale = $property->getStructure()->getLanguageCode();

        if (!\is_array($ids) || empty($ids)) {
            return [];
        }

        $collections = [];
        foreach ($ids as $id) {
            try {
                $collections[] = $this->collectionManager->getById($id, $locale);
            } catch (CollectionNotFoundException $e) {
                // @ignoreException: do not crash page if selection collection is deleted
            }
        }

        return $collections;
    }

    public function preResolve(PropertyInterface $property): void
    {
        $ids = $property->getValue();
        if (!\is_array($ids) || empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $this->collectionReferenceStore->add($id);
        }
    }
}
