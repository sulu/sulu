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

namespace Sulu\Content\Tests\Unit\Content\Application\DeduplicationStore;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\DeduplicationStore\DeduplicationStore;

class DeduplicationStoreTest extends TestCase
{
    public function testGetIdsReturnsTheNonEmptyIdsOfTheResourceKeyOnce(): void
    {
        $store = new DeduplicationStore();
        $store->add('media', '1', 2);
        $store->add('pages', 'a-uuid', '');
        $store->add('media', 1);

        self::assertSame(['1', '2'], $store->getIds('media'));
        self::assertSame(['a-uuid'], $store->getIds('pages'));
        self::assertSame([], $store->getIds('articles'));
    }

    public function testResetRemovesAllIds(): void
    {
        $store = new DeduplicationStore();
        $store->add('media', '1');

        $store->reset();

        self::assertSame([], $store->getIds('media'));
    }
}
