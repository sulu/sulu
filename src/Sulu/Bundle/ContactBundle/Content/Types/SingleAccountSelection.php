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

use PHPCR\NodeInterface;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;

class SingleAccountSelection extends ComplexContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var AccountManager
     */
    protected $accountManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $accountReferenceStore;

    public function __construct(
        AccountManager $accountManager,
        ReferenceStoreInterface $accountReferenceStore
    ) {
        $this->accountManager = $accountManager;
        $this->accountReferenceStore = $accountReferenceStore;
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = null;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName());
        }

        $property->setValue($value);
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if (null != $value) {
            if (\is_array($value)) {
                @trigger_deprecation(
                    'sulu/sulu',
                    '2.2',
                    'Passing a serialized account to the SingleAccountSelection deprecated. Please use an id instead.'
                );

                $node->setProperty($property->getName(), $value['id']);
            } else {
                $node->setProperty($property->getName(), $value);
            }
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        // if exist remove property of node
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    public function getContentData(PropertyInterface $property): ?Account
    {
        $id = $property->getValue();

        if (!$id) {
            return null;
        }

        try {
            return $this->accountManager->getById($id, $property->getStructure()->getLanguageCode());
        } catch (EntityNotFoundException $e) {
            return null;
        }
    }

    public function preResolve(PropertyInterface $property)
    {
        $id = $property->getValue();
        if (!$id) {
            return;
        }

        $this->accountReferenceStore->add($id);
    }
}
