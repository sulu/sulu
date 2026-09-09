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

namespace Sulu\Content\Tests\Unit\Application\ContentResolver;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\ContentDeduplicationTracker;

class ContentDeduplicationTrackerTest extends TestCase
{
    public function testGetAllReturnsEmptyArrayForUnknownResourceKey(): void
    {
        $tracker = new ContentDeduplicationTracker();

        $this->assertSame([], $tracker->getAll('pages'));
    }

    public function testAddGroupsIdsByResourceKeyAndDeduplicates(): void
    {
        $tracker = new ContentDeduplicationTracker();
        $tracker->add('pages', 'page-1');
        $tracker->add('pages', 'page-2');
        $tracker->add('pages', 'page-1'); // duplicate must be ignored
        $tracker->add('articles', 42);

        $this->assertSame(['page-1', 'page-2'], $tracker->getAll('pages'));
        $this->assertSame(['42'], $tracker->getAll('articles'));
    }

    public function testResetClearsAllTrackedIds(): void
    {
        $tracker = new ContentDeduplicationTracker();
        $tracker->add('pages', 'page-1');

        $tracker->reset();

        $this->assertSame([], $tracker->getAll('pages'));
    }
}