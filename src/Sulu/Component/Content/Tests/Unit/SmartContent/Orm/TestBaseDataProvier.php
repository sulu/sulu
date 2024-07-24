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

namespace Sulu\Component\Content\Tests\Unit\SmartContent\Orm;

use Sulu\Component\SmartContent\ItemInterface;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;

class TestBaseDataProvier extends BaseDataProvider
{
    /** @var array<ItemInterface> */
    public array $returnValue = [];

    /**
     * Decorates result as data item.
     *
     * @param object[] $data
     *
     * @return ItemInterface[]
     */
    protected function decorateDataItems(array $data): array
    {
        return $this->returnValue;
    }
}
