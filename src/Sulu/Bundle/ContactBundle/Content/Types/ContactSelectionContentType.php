<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Content\Types;

use JMS\Serializer\Serializer;
use PHPCR\NodeInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Util\IdsHandlingTrait;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;

/**
 * ContentType for Contact.
 */
class ContactSelectionContentType extends ComplexContentType
{
    use IdsHandlingTrait;

    /**
     * @var string
     */
    private $template;

    /**
     * @var ContactManagerInterface
     */
    private $contactManager;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct($template, $contactManager, $serializer)
    {
        $this->template = $template;
        $this->contactManager = $contactManager;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return ContentTypeInterface::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $values = [];
        if ($node->hasProperty($property->getName())) {
            $values = $node->getPropertyValue($property->getName());
        }
        $this->setData($values, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
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
        $value = $property->getValue();
        $node->setProperty($property->getName(), ($value === null ? [] : $value));
    }

    /**
     * {@inheritdoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $ids = $property->getValue();

        if ($ids === null || !is_array($ids) || count($ids) === 0) {
            return [];
        }

        $locale = $property->getStructure()->getLanguageCode();
        $contacts = $this->contactManager->getByIds($ids, $locale);
        $result = $this->sortByIds($ids, $contacts);

        return $this->serializer->serialize($result, 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set data to given property.
     *
     * @param array $data
     * @param PropertyInterface $property
     */
    protected function setData($data, PropertyInterface $property)
    {
        $refs = isset($data) ? $data : [];
        $property->setValue($refs);
    }
}
