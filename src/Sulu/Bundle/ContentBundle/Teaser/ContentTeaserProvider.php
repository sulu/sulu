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

use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\ContentBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderInterface;

/**
 * Teaser provider for content-pages.
 */
class ContentTeaserProvider implements TeaserProviderInterface
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @param SearchManagerInterface $searchManager
     */
    public function __construct(SearchManagerInterface $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return new TeaserConfiguration('sulu-content.teaser.content', 'teaser-selection/content@sulucontent');
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $ids, $locale)
    {
        $statements = array_map(
            function ($item) {
                return sprintf('__id:"%s"', $item);
            },
            $ids
        );

        $result = [];
        $searchResult = $this->searchManager
            ->createSearch(implode(' OR ', $statements))
            ->indexes($this->getPageIndexes())
            ->execute();

        /** @var QueryHit $item */
        foreach ($searchResult as $item) {
            $title = $item->getDocument()->getField('title')->getValue();
            $excerptTitle = $item->getDocument()->getField('excerptTitle')->getValue();

            $result[] = new Teaser(
                $item->getId(),
                'content',
                $locale,
                ('' !== $excerptTitle ? $excerptTitle : $title),
                $item->getDocument()->getField('excerptDescription')->getValue(),
                $item->getDocument()->getField('excerptMore')->getValue(),
                $item->getDocument()->getField('__url')->getValue(),
                $this->getMedia(json_decode($item->getDocument()->getField('excerptImages')->getValue(), true))
            );
        }

        return $result;
    }

    /**
     * Returns media-id.
     *
     * @param array $images
     *
     * @return int|null
     */
    private function getMedia(array $images)
    {
        if (!array_key_exists('ids', $images) || 0 === count($images['ids'])) {
            return;
        }

        return $images['ids'][0];
    }

    /**
     * Returns page indexes.
     *
     * @return array
     */
    private function getPageIndexes()
    {
        return array_filter(
            $this->searchManager->getIndexNames(),
            function ($index) {
                return strpos($index, 'page_') === 0;
            }
        );
    }
}
