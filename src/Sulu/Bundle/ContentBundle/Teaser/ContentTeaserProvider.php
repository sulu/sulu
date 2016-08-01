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

use Sulu\Bundle\ContentBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\ContentBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Teaser provider for content-pages.
 */
class ContentTeaserProvider implements TeaserProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
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
                return sprintf('[jcr:uuid] = "%s"', $item);
            },
            $ids
        );

        $query = $this->documentManager->createQuery(
            sprintf('SELECT * FROM [nt:unstructured] WHERE %s', implode(' OR ', $statements)),
            $locale
        );

        $result = [];
        foreach ($query->execute() as $document) {
            $excerptData = $document->getExtensionsData()['excerpt'];
            $uuid = $document->getUuid();
            $title = !empty($excerptData['title']) ? $excerptData['title'] : $document->getTitle();
            $description = $excerptData['description'];
            $more = $excerptData['more'];
            $mediaId = $this->getMedia($excerptData['images']);

            $result[] = new Teaser(
                $uuid,
                'content',
                $locale,
                $title,
                $description,
                $more,
                $document->getResourceSegment(),
                $mediaId
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
}
