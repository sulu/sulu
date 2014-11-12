<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use Psr\Log\LoggerInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\PropertyValueInterface;
use Sulu\Component\Util\ArrayableInterface;

/**
 * content type for internal links selection
 * @package Sulu\Bundle\ContentBundle\Content\Types
 */
class Select extends ComplexContentType
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $template;

    public function __construct(
        LoggerInterface $logger,
        $template
    )
    {
        $this->logger = $logger;
        $this->template = $template;
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
        $data = $node->getPropertyValueWithDefault($property->getName(), null);
        $this->setData($data, $property, $webspaceKey, $languageCode);

        $property->getValues();
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
        if ($data instanceof ArrayableInterface) {
            $data = $data->toArray();
        }

        $this->setData($data, $property, $webspaceKey, $languageCode);
    }

    /**
     * set data to property
     * @param string[] $data ids of images
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     */
    private function setData($data, PropertyInterface $property, $webspaceKey, $languageCode)
    {
        $property->setValue($this->getDataPropertyValues($data, $languageCode, $property));
    }

    /**
     * @param array $data
     * @param string $languageCode
     * @param PropertyInterface $property
     * @return array
     */
    private function getDataPropertyValues($data, $languageCode, PropertyInterface $property)
    {
        $params = $property->getParams();
        $valueParam = $params['value'];
        $valueName = $params['valueName'];

        $propertyValues = array();
        foreach ($data as $value) {
            $propertyValue = array();
            $actualPropertyValue = $this->getSelectedPropertyValue($property->getValues(), $valueParam, $value);
            $propertyValue[$valueName] = null;
            if ($actualPropertyValue) {
                $propertyValue = array_merge(
                    $actualPropertyValue->getAttributes(),
                    $actualPropertyValue->getMeta()->getLanguageMeta($languageCode)
                );
            }
            $propertyValue[$valueParam] = $value;
            $propertyValues[] = $propertyValue;
        }

        return $propertyValues;
    }

    /**
     * @param PropertyValueInterface[] $values
     * @param $valueParam
     * @param $search
     * @return PropertyValueInterface
     */
    private function getSelectedPropertyValue($values, $valueParam, $search)
    {
        if (!empty($values)) {
            foreach ($values as $value) {
                if ($value->getAttribute($valueParam) == $search) {
                    return $value;
                } else {
                    $value = $this->getSelectedPropertyValue($value->getChildren(), $valueParam, $search);
                    if ($value) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams()
    {
        return array(
            'multiple' => false,
            'preSelectedElements' => array(),
            'valueName' => 'title',
            'value' => 'id',
            'defaultLabel' => null,
            'icon' => null,
            'style' => null,
            'repeatSelect' => false,
            'fixedLabel' => false,
            'disabled' => false,
        );
    }

    /**
     * @param $params
     * @return array
     */
    public function getParams($params)
    {
        return array_merge($this->getDefaultParams(), $params);
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

        $params = $this->getParams($property->getParams());
        if ($params['multiple']) {
            $saveValue = array();
            if (isset($value['id'])) {
                $saveValue[] = $value['id'];
            }
        } else {
            $saveValue = null;
            if (isset($value['id'])) {
                $saveValue = $value['id'];
            }
        }

        $node->getProperty($property->getName())->remove(); // content type changes
        $node->setProperty($property->getName(), $saveValue);
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
        $node->getProperty($property->getName())->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritDoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        return $property->getValue();
    }
}
