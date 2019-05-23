<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * content type for image selection.
 */
class MediaSelectionContentType extends ComplexContentType implements ContentTypeExportInterface, PreResolvableContentTypeInterface
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    public function __construct(MediaManagerInterface $mediaManager, ReferenceStoreInterface $referenceStore)
    {
        $this->mediaManager = $mediaManager;
        $this->referenceStore = $referenceStore;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'types' => new PropertyParameter('types', null),
            'formats' => new PropertyParameter('formats', []),
        ];
    }

    /** @param $params
     *
     * @return PropertyParameter[]
     */
    public function getParams($params)
    {
        return array_merge($this->getDefaultParams(), $params);
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
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{"ids": []}'), true);

        $property->setValue($data);
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
        if ($value instanceof ArrayableInterface) {
            $value = $value->toArray();
        }

        // if whole smart-content container is pushed
        if (isset($value['data'])) {
            unset($value['data']);
        }

        // set value to node
        $node->setProperty($property->getName(), json_encode($value));
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
        $data = $property->getValue();

        $params = $this->getParams($property->getParams());
        $types = $params['types']->getValue();

        $container = new MediaSelectionContainer(
            isset($data['config']) ? $data['config'] : [],
            isset($data['displayOption']) ? $data['displayOption'] : '',
            isset($data['ids']) ? $data['ids'] : [],
            $property->getStructure()->getLanguageCode(),
            $types,
            $this->mediaManager
        );

        return $container->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        if (!is_array($propertyValue)) {
            return '';
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
        $property->setValue(json_decode($value, true));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        $data = $property->getValue();
        if (!isset($data['ids']) || !is_array($data['ids'])) {
            return;
        }

        foreach ($data['ids'] as $id) {
            $this->referenceStore->add($id);
        }
    }
}
