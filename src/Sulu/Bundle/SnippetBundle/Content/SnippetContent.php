<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use PHPCR\NodeInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;
use PHPCR\PropertyType;
use Sulu\Component\Content\Mapper\ContentMapper;
use Sulu\Component\Content\ContentTypeInterface;

/**
 * ContentType for TextEditor
 */
class SnippetContent extends ComplexContentType
{
    protected $contentMapper;

    public function __construct(ContentMapper $contentMapper)
    {
        $this->contentMapper = $contentMapper;
    }

    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    public function getTemplate()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $refs = $node->getPropertyValueWithDefault($property->getName(), array());
        $snippets = array();

        foreach ($refs as $ref) {
            $snippets[] = $this->contentMapper->loadByNode($ref, $languageCode, $webspaceKey);
        }

        $property->setValue($snippets);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        // ??
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
        $snippetReferences = $property->getValue();
        foreach ($snippetReferences as $snippetReference) {
            $node->setProperty($property->getName(), $snippetReferences, PropertyType::REFERENCE);
        }
    }

    /**
     * remove property from given node
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultParams()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return array();
    }

    /**
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $snippets = $property->getValue();
        $serializedSnippets = array();

        foreach ($snippets as $snippet) {
            $serializedSnippets[] = $snippet->toArray();
        }

        return $serializedSnippets;
    }
}
