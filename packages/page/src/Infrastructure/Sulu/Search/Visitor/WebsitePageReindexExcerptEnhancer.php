<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Search\Visitor;

use Doctrine\ORM\QueryBuilder;

/**
 * @internal if you need to override this service, create a new service based on the WebsitePageReindexProviderEnhancerInterface
 * instead of extending this class
 *
 * @final
 */
class WebsitePageReindexExcerptEnhancer implements WebsitePageReindexProviderEnhancerInterface
{
    public function enhanceQuery(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->addSelect('dimensionContent.excerptData');
    }

    public function enhanceDocument(array $queryResult, array $document): array
    {
        $excerptData = $queryResult['excerptData'] ?? [];
        if (!\is_array($excerptData)) {
            return $document;
        }

        $excerpt = [];
        /** @var list<string> */
        $content = \is_array($document['content'] ?? null) ? $document['content'] : [];

        $excerptTitle = $excerptData['title'] ?? null;
        if (\is_string($excerptTitle) && '' !== \trim($excerptTitle)) {
            $excerpt['title'] = \trim($excerptTitle);
            $content[] = \trim($excerptTitle);

            if ('' === ($document['title'] ?? '')) {
                $document['title'] = \trim($excerptTitle);
            }
        }

        $excerptDescription = $excerptData['description'] ?? null;
        if (\is_string($excerptDescription) && '' !== \trim($excerptDescription)) {
            $excerpt['description'] = \trim($excerptDescription);
            $content[] = \trim(\strip_tags($excerptDescription));
        }

        $excerptMore = $excerptData['more'] ?? null;
        if (\is_string($excerptMore) && '' !== \trim($excerptMore)) {
            $excerpt['more'] = \trim($excerptMore);
        }

        $image = $excerptData['image'] ?? null;
        if (\is_array($image) && isset($image['id']) && \is_numeric($image['id'])) {
            $excerpt['imageId'] = (int) $image['id'];

            if ('' === ($document['mediaId'] ?? '')) {
                $document['mediaId'] = (string) $image['id'];
            }
        }

        $icon = $excerptData['icon'] ?? null;
        if (\is_array($icon) && isset($icon['id']) && \is_numeric($icon['id'])) {
            $excerpt['iconId'] = (int) $icon['id'];
        }

        $document['content'] = $content;

        if ([] !== $excerpt) {
            $metadata = \is_array($document['metadata'] ?? null) ? $document['metadata'] : [];
            $metadata['excerpt'] = $excerpt;
            $document['metadata'] = $metadata;
        }

        return $document;
    }
}
