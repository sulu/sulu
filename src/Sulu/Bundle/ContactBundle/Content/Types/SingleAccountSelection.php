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
            try {
                $account = $this->accountManager->getById(
                    $node->getPropertyValue($property->getName()),
                    $property->getStructure()->getLanguageCode()
                );

                $value = [
                    'id' => $account->getId(),
                    'name' => $account->getName(),
                ];
            } catch (EntityNotFoundException $e) {
                $value = null;
            }
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
        $account = $property->getValue();
        if (null != $account) {
            $node->setProperty($property->getName(), $account['id']);
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
        $account = $property->getValue();

        if (!isset($account['id'])) {
            return null;
        }

        return $this->accountManager->getById($account['id'], $property->getStructure()->getLanguageCode());
    }

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $account = $property->getValue();
        if (!$account || !array_key_exists('id', $account)) {
            return;
        }

        $this->accountReferenceStore->add($account['id']);
    }
}
