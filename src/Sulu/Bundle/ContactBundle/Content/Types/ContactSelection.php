<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Content\Types;

use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

class ContactSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var ContactRepositoryInterface
     */
    protected $contactRepository;

    /**
     * @var ReferenceStoreInterface
     */
    private $contactReferenceStore;

    public function __construct(
        ContactRepositoryInterface $contactRepository,
        ReferenceStoreInterface $contactReferenceStore
    ) {
        $this->contactRepository = $contactRepository;
        $this->contactReferenceStore = $contactReferenceStore;

        parent::__construct('ContactSelection');
    }

    /**
     * @return ContactInterface[]
     */
    public function getContentData(PropertyInterface $property): array
    {
        $ids = $property->getValue();
        if (!\is_array($ids) || empty($ids)) {
            return [];
        }

        $contacts = $this->contactRepository->findByIds($ids);

        $idPositions = \array_flip($ids);
        \usort($contacts, function(ContactInterface $a, ContactInterface $b) use ($idPositions) {
            return $idPositions[$a->getId()] - $idPositions[$b->getId()];
        });

        return $contacts;
    }

    public function preResolve(PropertyInterface $property)
    {
        $ids = $property->getValue();
        if (!\is_array($ids) || empty($ids)) {
            return [];
        }

        foreach ($ids as $id) {
            $this->contactReferenceStore->add($id);
        }
    }
}
