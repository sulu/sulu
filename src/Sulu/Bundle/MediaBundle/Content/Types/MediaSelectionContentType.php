<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeExportInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * content type for image selection.
 */
class MediaSelectionContentType extends ComplexContentType implements ContentTypeExportInterface
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var string
     */
    private $template;

    public function __construct($mediaManager, $template)
    {
        $this->mediaManager = $mediaManager;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'types' => new PropertyParameter('types', null),
            'defaultDisplayOption' => new PropertyParameter('defaultDisplayOption', 'top'),
            'displayOptions' => new PropertyParameter(
                'displayOptions',
                [
                    'leftTop' => new PropertyParameter('leftTop', true),
                    'top' => new PropertyParameter('top', true),
                    'rightTop' => new PropertyParameter('rightTop', true),
                    'left' => new PropertyParameter('left', true),
                    'middle' => new PropertyParameter('middle', false),
                    'right' => new PropertyParameter('right', true),
                    'leftBottom' => new PropertyParameter('leftBottom', true),
                    'bottom' => new PropertyParameter('bottom', true),
                    'rightBottom' => new PropertyParameter('rightBottom', true),
                ],
                'collection'
            ),
            'formats' => new PropertyParameter('formats', []),
        ];
    }

    /**
     * @param $params
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
    public function getType()
    {
        return self::PRE_SAVE;
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
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{}'), true);

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
    public function getTemplate()
    {
        return $this->template;
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
}
