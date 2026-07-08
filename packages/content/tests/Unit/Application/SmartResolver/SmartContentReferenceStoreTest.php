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

namespace Sulu\Content\Tests\Unit\Application\SmartResolver;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\SmartResolver\SmartContentReferenceStore;

class SmartContentReferenceStoreTest extends TestCase
{
    public function testGetAllReturnsEmptyArrayForUnknownType(): void
    {
        $store = new SmartContentReferenceStore();

        $this->assertSame([], $store->getAll('pages'));
    }

    public function testAddGroupsIdsByTypeAndDeduplicates(): void
    {
        $store = new SmartContentReferenceStore();
        $store->add('pages', 'page-1');
        $store->add('pages', 'page-2');
        $store->add('pages', 'page-1'); // duplicate must be ignored
        $store->add('articles', 42);

        $this->assertSame(['page-1', 'page-2'], $store->getAll('pages'));
        $this->assertSame(['42'], $store->getAll('articles'));
    }

    public function testResetClearsAllStoredIds(): void
    {
        $store = new SmartContentReferenceStore();
        $store->add('pages', 'page-1');

        $store->reset();

        $this->assertSame([], $store->getAll('pages'));
    }
}
