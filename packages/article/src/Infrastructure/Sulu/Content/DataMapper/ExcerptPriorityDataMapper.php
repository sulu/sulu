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

namespace Sulu\Article\Infrastructure\Sulu\Content\DataMapper;

use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Content\Application\ContentDataMapper\DataMapper\DataMapperInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Persists the localized "excerptPriority" field of the article excerpt tab
 * (see config/forms/article_excerpt_priority.xml) into its dedicated indexed column.
 */
class ExcerptPriorityDataMapper implements DataMapperInterface
{
    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof ArticleDimensionContentInterface) {
            return;
        }

        if (!\array_key_exists('excerptPriority', $data)) {
            return;
        }

        $excerptPriority = $data['excerptPriority'];

        $localizedDimensionContent->setExcerptPriority(
            \is_numeric($excerptPriority) ? (int) $excerptPriority : 0,
        );
    }
}
