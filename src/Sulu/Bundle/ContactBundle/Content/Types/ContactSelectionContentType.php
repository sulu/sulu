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

use PHPCR\NodeInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;

/**
 * ContentType for Contact.
 */
class ContactSelectionContentType extends ComplexContentType
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var ContactManagerInterface
     */
    private $contactManager;

    /**
     * ContactSelectionContentType constructor.
     *
     * @param ContactManagerInterface $contactManager
     * @param string $template
     */
    public function __construct($contactManager, $template)
    {
        $this->contactManager = $contactManager;
        $this->template = $template;
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
        $values = array();
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
        $node->setProperty($property->getName(), ($value === null ? array() : $value));
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
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $locale = $property->getStructure()->getLanguageCode();

        $contacts = array();
        $ids = $property->getValue();

        if ($ids === null || !is_array($ids)) {
            return array();
        }

        foreach ($ids as $id) {
            $contact = $this->contactManager->getById($id, $locale);
            if ($contact !== null) {
                $contacts[] = $contact;
            }
        }

        return $contacts;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultValue()
    {
        return array();
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
        $refs = isset($data) ? $data : array();
        $property->setValue($refs);
    }
}
