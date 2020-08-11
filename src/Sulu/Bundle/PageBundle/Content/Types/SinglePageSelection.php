<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;

/**
 * ContentType for SinglePageSelection.
 */
class SinglePageSelection extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var ?SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(
        ReferenceStoreInterface $referenceStore,
        SecurityCheckerInterface $securityChecker = null
    ) {
        parent::__construct('SinglePageSelection', '');

        $this->referenceStore = $referenceStore;
        $this->securityChecker = $securityChecker;
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

        if (null !== $node->getIdentifier() && $value === $node->getIdentifier()) {
            throw new \InvalidArgumentException('Single page selection node cannot reference itself');
        }

        parent::write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $this->defaultValue;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName());
        }

        // the RedirectType subscriber sets the internal link as a reference
        if ($value instanceof NodeInterface) {
            $value = $value->getIdentifier();
        }

        $property->setValue($value);

        return $value;
    }

    public function getContentData(PropertyInterface $property)
    {
        if ($this->securityChecker
            && !$this->securityChecker->hasPermission(
                new SecurityCondition(
                    PageAdmin::SECURITY_CONTEXT_PREFIX . $property->getStructure()->getWebspaceKey(),
                    $property->getStructure()->getLanguageCode(),
                    SecurityBehavior::class,
                    $property->getValue()
                ),
                PermissionTypes::VIEW
            )
        ) {
            return null;
        }

        return parent::getContentData($property);
    }

    public function preResolve(PropertyInterface $property)
    {
        $uuid = $property->getValue();
        if (!$uuid) {
            return;
        }

        $this->referenceStore->add($uuid);
    }
}
