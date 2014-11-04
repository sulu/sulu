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
        $data = $node->getPropertyValueWithDefault($property->getName(), array());
        $this->setData($data, $property, $webspaceKey, $languageCode);
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
        $property->setValue($this->getDataWithTitles($data, $languageCode));
    }

    /**
     * @param array $data
     * @param string $languageCode
     * @return array
     */
    private function getDataWithTitles($data, $languageCode)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams()
    {
        return array(
            'multiple' => false,
            'preSelected' => array(),
            'valueName' => 'name',
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

        if ($value instanceof ArrayableInterface) {
            $value = $value->toArray();
        }

        // if whole container is pushed
        if (isset($value['data'])) {
            unset($value['data']);
        }

        if (isset($value['ids'])) {
            // remove not existing ids
            $session = $node->getSession();
            $selectedNodes = $session->getNodesByIdentifier($value['ids']);
            $ids = array();
            foreach ($selectedNodes as $selectedNode) {
                $ids[] = $selectedNode->getIdentifier();
            }
            $value['ids'] = $ids;
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
