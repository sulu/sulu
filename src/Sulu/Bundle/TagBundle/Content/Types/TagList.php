<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\ContentTypeInterface;

/**
 * Content Type for the TagList, uses the TagManager-Service and the AutoCompleteList from Husky.
 */
class TagList extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * Responsible for saving the tags in the database.
     *
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * Holds the template for rendering this content type in the admin.
     *
     * @var string
     */
    private $template;

    public function __construct(TagManagerInterface $tagManager, $template)
    {
        $this->tagManager = $tagManager;
        $this->template = $template;
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE.
     *
     * @return int
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $tags = $this->tagManager->resolveTagIds($node->getPropertyValueWithDefault($property->getName(), []));
        $property->setValue($tags);
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
        $tagIds = [];
        $tags = $property->getValue() === null ? [] : $property->getValue();

        foreach ($tags as $tag) {
            $tagIds[] = $this->tagManager->findOrCreateByName($tag, $userId)->getId();
        }

        $node->setProperty($property->getName(), $tagIds);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * returns a template to render a form.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        if (false === is_array($propertyValue)) {
            return '';
        }

        foreach ($propertyValue as &$propertyValueItem) {
            if (is_string($propertyValueItem)) {
                $tag = $this->tagManager->findByName($propertyValueItem);
                if ($tag) {
                    $propertyValueItem = $tag->getId();
                }
            }
        }

        if (!empty($propertyValue)) {
            return json_encode($propertyValue);
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $tagNames = [];
        $tagIds = json_decode($value);
        if (!empty($tagIds)) {
            $tagNames = $this->tagManager->resolveTagIds($tagIds);
        }

        $property->setValue($tagNames);
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
