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
use Sulu\Component\Content\ContentTypeExportInterface;
use Symfony\Component\Intl\Intl;

/**
 * ContentType for TextEditor.
 */
class LocationContentType extends ComplexContentType implements ContentTypeExportInterface
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
        return [
            'countries' => new PropertyParameter('countries', $this->getCountries(), 'collection'),
            'mapProviders' => new PropertyParameter(
                'mapProviders',
                $this->mapManager->getProvidersAsArray(),
                'collection'
            ),
            'defaultProvider' => new PropertyParameter('defaultProvider', $this->mapManager->getDefaultProviderName()),
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
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
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

    /**
     * Returns array of countries with the country-code as array key.
     *
     * @return array
     */
    private function getCountries()
    {
        $countries = [];
        foreach (Intl::getRegionBundle()->getCountryNames() as $countryCode => $countryName) {
            $countries[strtolower($countryCode)] = new PropertyParameter(strtolower($countryCode), $countryName);
        }

        return $countries;
    }

    /**
     * {@inheritdoc}
     */
    public function exportData($propertyValue)
    {
        if (false === is_string($propertyValue)) {
            return '';
        }

        return $propertyValue;
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
        $property->setValue($value);
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
