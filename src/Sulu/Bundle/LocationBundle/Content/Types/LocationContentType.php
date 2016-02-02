<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\LocationBundle\Map\MapManager;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;

/**
 * ContentType for TextEditor.
 */
class LocationContentType extends ComplexContentType
{
    /**
     * @var NodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var string
     */
    private $template;

    /**
     * @var MapManager
     */
    private $mapManager;

    /**
     * @var string
     */
    private $geolocatorName;

    public function __construct(
        NodeRepositoryInterface $nodeRepository,
        $template,
        MapManager $mapManager,
        $geolocatorName
    ) {
        $this->nodeRepository = $nodeRepository;
        $this->template = $template;
        $this->mapManager = $mapManager;
        $this->geolocatorName = $geolocatorName;
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
        // Need a service to provide countries, see: https://github.com/sulu-cmf/SuluContactBundle/issues/121
        return [
            'countries' => new PropertyParameter(
                'countries',
                [
                    'at' => new PropertyParameter('at', 'Austria'),
                    'fr' => new PropertyParameter('fr', 'France'),
                    'ch' => new PropertyParameter('ch', 'Switzerland'),
                    'de' => new PropertyParameter('de', 'Germany'),
                    'gb' => new PropertyParameter('gb', 'Great Britain'),
                ],
                'collection'
            ),
            'mapProviders' => new PropertyParameter(
                'mapProviders',
                $this->mapManager->getProvidersAsArray(),
                'collection'
            ),
            'defaultProvider' => new PropertyParameter(
                'defaultProvider',
                $this->mapManager->getDefaultProviderName()
            ),
            'geolocatorName' => new PropertyParameter('geolocatorName', $this->geolocatorName),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * @param $data
     * @param PropertyInterface $property
     * @param string            $webspaceKey
     * @param string            $languageCode
     * @param string            $segmentKey
     * @param bool              $preview
     */
    protected function setData(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        $preview = false
    ) {
        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $data = json_decode($node->getPropertyValueWithDefault($property->getName(), '{}'), true);
        $this->setData($data, $property, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $this->setData($data, $property, $webspaceKey, $languageCode, $segmentKey, true);
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
        $node->setProperty($property->getName(), json_encode($value));
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
}
