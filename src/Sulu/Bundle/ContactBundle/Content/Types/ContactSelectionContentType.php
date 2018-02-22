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

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use PHPCR\NodeInterface;
use Sulu\Bundle\ContactBundle\Api\Account;
use Sulu\Bundle\ContactBundle\Api\Contact;
use Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface;
use Sulu\Bundle\ContactBundle\Util\IdConverterInterface;
use Sulu\Bundle\ContactBundle\Util\IndexComparatorInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;

/**
 * ContentType for Contact.
 */
class ContactSelectionContentType extends ComplexContentType implements ContentTypeExportInterface, PreResolvableContentTypeInterface
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
     * @var ContactManagerInterface
     */
    private $accountManager;

    /**
     * @var SerializerInterface
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
    private $accountReferenceStore;

    /**
     * @var ReferenceStoreInterface
     */
    private $contactReferenceStore;

    public function __construct(
        $template,
        ContactManagerInterface $contactManager,
        ContactManagerInterface $accountManager,
        SerializerInterface $serializer,
        IdConverterInterface $converter,
        IndexComparatorInterface $comparator,
        ReferenceStoreInterface $accountReferenceStore,
        ReferenceStoreInterface $contactReferenceStore
    ) {
        $this->template = $template;
        $this->contactManager = $contactManager;
        $this->accountManager = $accountManager;
        $this->serializer = $serializer;
        $this->converter = $converter;
        $this->comparator = $comparator;
        $this->accountReferenceStore = $accountReferenceStore;
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
        $value = $property->getValue();
        $locale = $property->getStructure()->getLanguageCode();

        if (null === $value || !is_array($value) || 0 === count($value)) {
            return [];
        }

        $ids = $this->converter->convertIdsToGroupedIds($value, ['a' => [], 'c' => []]);

        $accounts = $this->accountManager->getByIds($ids['a'], $locale);
        $contacts = $this->contactManager->getByIds($ids['c'], $locale);

        $result = array_merge($accounts, $contacts);
        @usort(
            $result,
            function ($a, $b) use ($value) {
                $typeA = $a instanceof Contact ? 'c' : 'a';
                $typeB = $b instanceof Contact ? 'c' : 'a';

                return $this->comparator->compare($typeA . $a->getId(), $typeB . $b->getId(), $value);
            }
        );

        return array_map(
            function ($entity) {
                $groups = ['fullContact', 'partialAccount'];
                if ($entity instanceof Account) {
                    $groups = ['fullAccount', 'partialContact'];
                }

                $groups[] = 'partialCategory';

                return $this->serializer->serialize(
                    $entity,
                    'array',
                    SerializationContext::create()->setGroups($groups)->setSerializeNull(true)
                );
            },
            $result
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
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'contact' => new PropertyParameter('contact', true),
            'account' => new PropertyParameter('account', true),
        ];
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

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $value = $property->getValue();
        if (null === $value || !is_array($value) || 0 === count($value)) {
            return [];
        }

        $ids = $this->converter->convertIdsToGroupedIds($value, ['a' => [], 'c' => []]);

        foreach ($ids['a'] as $account) {
            $this->accountReferenceStore->add($account);
        }

        foreach ($ids['c'] as $contact) {
            $this->contactReferenceStore->add($contact);
        }
    }
}
