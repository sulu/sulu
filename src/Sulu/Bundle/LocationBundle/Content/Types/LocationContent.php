<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Bundle\LocationBundle\Map\MapManager;

/**
 * ContentType for TextEditor
 */
class LocationContent extends ComplexContentType
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

    function __construct(NodeRepositoryInterface $nodeRepository, $template, MapManager $mapManager)
    {
        $this->nodeRepository = $nodeRepository;
        $this->template = $template;
        $this->mapManager = $mapManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultParams()
    {
        // Need a service to provide countries, see: https://github.com/sulu-cmf/SuluContactBundle/issues/121
        return array(
            'countries' => array(
                'at' => 'Austria',
                'fr' => 'France',
                'gb' => 'Great Britain'
            ),
            'mapProviders' => $this->mapManager->getProvidersAsArray(),
            'defaultProvider' => $this->mapManager->getDefaultProviderName(),
            'geolocators' => array('leaflet'),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * @param $data
     * @param PropertyInterface $property
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     * @param bool $preview
     */
    protected function setData(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey,
        $preview = false
    )
    {
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
    )
    {
        $value = $property->getValue();
        $node->setProperty($property->getName(), json_encode($value));
    }

    /**
     * {@inheritDoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        // TODO: Implement remove() method.
    }
}

