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
use Sulu\Bundle\ContentBundle\ReferenceStore\ChainReferenceStore;
use Sulu\Bundle\ContentBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\ContentBundle\ReferenceStore\ReferenceStoreNotExistsException;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPoolInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Provides content-type for selecting teasers.
 */
class TeaserContentType extends SimpleContentType implements PreResolvableContentTypeInterface
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
     * @var ReferenceStoreInterface
     */
    private $referenceStore;

    /**
     * @param string $template
     * @param TeaserProviderPoolInterface $providerPool
     * @param TeaserManagerInterface $teaserManager
     * @param ReferenceStoreInterface $referenceStore
     */
    public function __construct(
        $template,
        TeaserProviderPoolInterface $providerPool,
        TeaserManagerInterface $teaserManager,
        ReferenceStoreInterface $referenceStore
    ) {
        parent::__construct('teaser_selection');

        $this->template = $template;
        $this->teaserProviderPool = $providerPool;
        $this->teaserManager = $teaserManager;
        $this->referenceStore = $referenceStore;
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
        return $this->teaserManager->find($this->getItems($property), $property->getStructure()->getLanguageCode());
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $this->getValue($property);
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

    /**
     * {@inheritdoc}
     */
    public function preResolve(PropertyInterface $property)
    {
        foreach ($this->getItems($property) as $item) {
            try {
                $this->referenceStore->add($item['type'] . ChainReferenceStore::DELIMITER . $item['id']);
            } catch (ReferenceStoreNotExistsException $exception) {
                // ignore not existing stores
            }
        }
    }

    /**
     * Returns items.
     *
     * @param PropertyInterface $property
     *
     * @return array
     */
    private function getItems(PropertyInterface $property)
    {
        $value = $this->getValue($property);
        if (!is_array($value['items']) || 0 === count($value['items'])) {
            return [];
        }

        return $value['items'];
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
}
