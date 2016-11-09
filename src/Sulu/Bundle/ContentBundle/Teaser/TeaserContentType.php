<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Teaser;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides content-type for selecting teasers.
 */
class TeaserContentType extends SimpleContentType
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var TeaserProviderPoolInterface
     */
    private $teaserProviderPool;

    /**
     * @var TeaserManagerInterface
     */
    private $teaserManager;

    /**
     * @param string $template
     * @param TeaserProviderPoolInterface $providerPool
     * @param TeaserManagerInterface $teaserManager
     */
    public function __construct(
        $template,
        TeaserProviderPoolInterface $providerPool,
        TeaserManagerInterface $teaserManager
    ) {
        parent::__construct('teaser_selection');

        $this->template = $template;
        $this->teaserProviderPool = $providerPool;
        $this->teaserManager = $teaserManager;
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
            'providerConfiguration' => $this->teaserProviderPool->getConfiguration(),
            'present_as' => new PropertyParameter('present_as', [], 'collection'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function decodeValue($value)
    {
        return json_decode($value, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function encodeValue($value)
    {
        return json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $value = $this->getValue($property);
        if (!is_array($value['items']) || 0 === count($value['items'])) {
            return [];
        }

        return $this->teaserManager->find($value['items'], $property->getStructure()->getLanguageCode());
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $this->getValue($property);
    }

    /**
     * Returns property-value merged with defaults.
     *
     * @param PropertyInterface $property
     *
     * @return array
     */
    private function getValue(PropertyInterface $property)
    {
        $default = ['presentAs' => null, 'items' => []];
        if (!is_array($property->getValue())) {
            return $default;
        }

        return array_merge($default, $property->getValue());
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
        if (!empty($value)) {
            $value = json_decode($value);
        }

        parent::importData($node, $property, $value, $userId, $webspaceKey, $languageCode, $segmentKey);
    }
}
