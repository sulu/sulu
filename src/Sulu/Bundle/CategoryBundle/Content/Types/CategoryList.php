<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;

/**
 * Content Type for the CategoryList, uses the CategoryManager-Service and the Datagrid from Husky.
 */
class CategoryList extends ComplexContentType
{
    /**
     * Responsible for persisting the categories in the database.
     *
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    /**
     * Holds the template for rendering this content type in the admin.
     *
     * @var string
     */
    private $template;

    public function __construct(CategoryManagerInterface $categoryManager, $template)
    {
        $this->categoryManager = $categoryManager;
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
     * Sets the given array as values on the property.
     *
     * @param array             $data
     * @param PropertyInterface $property
     */
    protected function setData($data, PropertyInterface $property)
    {
        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $categoryIds = $node->getPropertyValueWithDefault($property->getName(), []);
        $this->setData($categoryIds, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $ids = $property->getValue();
        if (!is_array($ids) || empty($ids)) {
            return [];
        }

        $data = [];
        $categoryEntities = $this->categoryManager->findByIds($ids);
        $categoryApiEntities = $this->categoryManager->getApiObjects($categoryEntities, $property->getStructure()->getLanguageCode());

        foreach ($categoryApiEntities as $category) {
            $data[] = $category->toArray();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $this->setData($data, $property);
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
        $categoryIds = [];
        $value = $property->getValue();

        if (null === $value) {
            $node->setProperty($property->getName(), null);

            return;
        }

        foreach ($value as $category) {
            if (is_numeric($category)) {
                // int value for id
                $categoryIds[] = $category;
            } else {
                // full category object use only id to save
                $categoryIds[] = $category['id'];
            }
        }

        $node->setProperty($property->getName(), $categoryIds);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        if ($node->hasProperty($property->getName())) {
            $property = $node->getProperty($property->getName());
            $property->remove();
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
}
