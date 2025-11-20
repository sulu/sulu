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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ExcerptInterface;

class ExcerptDataMapper implements DataMapperInterface
{
    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data,
    ): void {
        if (!$localizedDimensionContent instanceof ExcerptInterface) {
            return;
        }

        $this->setExcerptData($localizedDimensionContent, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setExcerptData(ExcerptInterface $dimensionContent, array $data): void
    {
        $excerptData = $dimensionContent->getExcerptData();

        foreach ($data as $key => $value) {
            if (\str_starts_with($key, 'excerpt')) {
                $internalKey = \lcfirst(\substr($key, 7));
                $excerptData[$internalKey] = $value;
            }
        }

        $dimensionContent->setExcerptData($excerptData);
    }
}
