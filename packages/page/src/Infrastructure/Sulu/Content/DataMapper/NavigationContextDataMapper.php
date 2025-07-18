<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content\DataMapper;

use Sulu\Content\Application\ContentDataMapper\DataMapper\DataMapperInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;

class NavigationContextDataMapper implements DataMapperInterface
{
    /**
     * @param array{
     *     navigationContexts?: string[]
     * } $data
     */
    public function map(DimensionContentInterface $unlocalizedDimensionContent, DimensionContentInterface $localizedDimensionContent, array $data): void
    {
        if (!$localizedDimensionContent instanceof PageDimensionContentInterface) {
            return;
        }

        $this->setNavigationContextData($localizedDimensionContent, $data);
    }

    /**
     * @param array{
     *     navigationContexts?: string[]
     * } $data
     */
    private function setNavigationContextData(PageDimensionContentInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('navigationContexts', $data)) {
            $dimensionContent->setNavigationContexts($data['navigationContexts']);
        }
    }
}
