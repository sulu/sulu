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

use JMS\Serializer\SerializationContext;
use PHPCR\NodeInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Util\IdConverterInterface;
use Sulu\Bundle\ContactBundle\Util\IndexComparatorInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Serializer\ArraySerializerInterface;

/**
 * ContentType for Contact.
 */
class ContactSelection extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * @var ContactManagerInterface
     */
    private $contactManager;

    /**
     * @var ArraySerializerInterface
     */
    private $serializer;

    /**
     * @var IdConverterInterface
     */
    private $converter;

    /**
     * @var IndexComparatorInterface
     */
    private $comparator;

    /**
     * @var ReferenceStoreInterface
     */
    private $contactReferenceStore;

    public function __construct(
        ContactManagerInterface $contactManager,
        ArraySerializerInterface $serializer,
        IdConverterInterface $converter,
        IndexComparatorInterface $comparator,
        ReferenceStoreInterface $contactReferenceStore
    ) {
        $this->contactManager = $contactManager;
        $this->serializer = $serializer;
        $this->converter = $converter;
        $this->comparator = $comparator;
        $this->contactReferenceStore = $contactReferenceStore;
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

        $refs = isset($values) ? $values : [];
        $property->setValue($refs);
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
        $node->setProperty($property->getName(), (null === $value ? [] : $value));
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
        $locale = $property->getStructure()->getLanguageCode();

        if (null === $ids || !is_array($ids) || 0 === count($ids)) {
            return [];
        }

        $contacts = $this->contactManager->getByIds($ids, $locale);

        return array_map(
            function ($entity) {
                $groups = ['fullContact', 'partialCategory'];

                return $this->serializer->serialize(
                    $entity,
                    SerializationContext::create()->setGroups($groups)->setSerializeNull(true)
                );
            },
            $contacts
        );
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
    public function exportData($propertyValue)
    {
        if (is_array($propertyValue)) {
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
        $property->setValue(json_decode($value));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
