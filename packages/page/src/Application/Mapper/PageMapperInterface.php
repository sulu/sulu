<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\Mapper;

use Sulu\Page\Domain\Model\PageInterface;

/**
 * @experimental
 */
interface PageMapperInterface
{
    /**
     * @param mixed[] $data
     */
    public function mapPageData(PageInterface $page, array $data): void;
}
