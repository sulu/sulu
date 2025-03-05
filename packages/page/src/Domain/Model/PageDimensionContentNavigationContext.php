<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Domain\Model;

class PageDimensionContentNavigationContext
{
    public function __construct(
        private string $navigationContext,
        private PageDimensionContent $pageDimensionContent
    ) {
    }

    public function getNavigationContext(): string
    {
        return $this->navigationContext;
    }

    public function getPageDimensionContent(): PageDimensionContent
    {
        return $this->pageDimensionContent;
    }
}
