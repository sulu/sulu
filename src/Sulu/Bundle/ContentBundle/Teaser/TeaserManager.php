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

use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderPoolInterface;

/**
 * Provides functionality for interacting with teasers.
 */
class TeaserManager implements TeaserManagerInterface
{
    /**
     * @var TeaserProviderPoolInterface
     */
    private $providerPool;

    /**
     * @param TeaserProviderPoolInterface $providerPool
     */
    public function __construct(TeaserProviderPoolInterface $providerPool)
    {
        $this->providerPool = $providerPool;
    }

    /**
     * {@inheritdoc}
     */
    public function find($items, $locale)
    {
        if (0 === count($items)) {
            return [];
        }

        $result = [];
        foreach ($this->sort($items) as $type => $typeIds) {
            $teasers = $this->providerPool->getProvider($type)->find($typeIds, $locale);

            foreach ($teasers as $teaser) {
                $index = array_search(['type' => $type, 'id' => $teaser->getId()], $items);
                $result[$index] = $teaser;
            }
        }

        ksort($result);

        return array_values($result);
    }

    /**
     * Returns ids sorted by type.
     *
     * @param array $ids
     *
     * @return array
     */
    private function sort($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            if (!array_key_exists($id['type'], $result)) {
                $result[$id['type']] = [];
            }
            $result[$id['type']][] = $id['id'];
        }

        return $result;
    }
}
