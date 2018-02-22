<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;

/**
 * ContentType for SingleInternalLink.
 */
class SingleInternalLink extends SimpleContentType implements PreResolvableContentTypeInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @var string
     */
    private $template;

    /**
     * @param ReferenceStoreInterface $referenceStore
     * @param string $template
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureResolverInterface $structureResolver,
        ReferenceStoreInterface $referenceStore,
        $template
    ) {
        parent::__construct('SingleInternalLink', '');

        $this->contentMapper = $contentMapper;
        $this->structureResolver = $structureResolver;
        $this->referenceStore = $referenceStore;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
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
            throw new \InvalidArgumentException('Internal link node cannot reference itself');
        }

        parent::write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        return $this->loadStructure($property->getValue(), $property->getStructure()->getLanguageCode());
    }

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $uuid = $property->getValue();
        if (!$uuid) {
            return;
        }

        $this->referenceStore->add($uuid);
    }

    /**
     * Load structure by uuid and locale.
     *
     * @param string $uuid
     * @param string $locale
     * @param string $webspaceKey
     *
     * @return array|null
     */
    private function loadStructure($uuid, $locale, $webspaceKey = null)
    {
        if (!$uuid) {
            return null;
        }

        try {
            $contentStructure = $this->contentMapper->load(
                $uuid,
                $webspaceKey,
                $locale
            );

            return $this->structureResolver->resolve($contentStructure);
        } catch (DocumentNotFoundException $e) {
            $this->logger->error((string) $e);
        }

        return null;
    }
}
