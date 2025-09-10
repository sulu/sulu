<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\ReferenceStore;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Symfony\Component\Uid\Uuid;

class ReferenceStoreTest extends TestCase
{
    private ReferenceStore $referenceStore;

    protected function setUp(): void
    {
        $this->referenceStore = new ReferenceStore();
    }

    public function testAdd(): void
    {
        $this->referenceStore->add('123', 'pages');
        $this->referenceStore->add('456', 'articles');

        $tags = $this->referenceStore->getAll();

        self::assertContains('pages-123', $tags);
        self::assertContains('articles-456', $tags);
        self::assertCount(2, $tags);
    }

    public function testAddWithUuid(): void
    {
        $uuid1 = Uuid::v7()->toRfc4122();
        $uuid2 = Uuid::v7()->toRfc4122();

        $this->referenceStore->add($uuid1, 'pages');
        $this->referenceStore->add($uuid2, 'articles');

        $tags = $this->referenceStore->getAll();

        self::assertContains($uuid1, $tags);
        self::assertContains($uuid2, $tags);
        self::assertCount(2, $tags);
    }

    public function testAddDuplicates(): void
    {
        $this->referenceStore->add('123', 'pages');
        $this->referenceStore->add('123', 'pages'); // Duplicate

        $tags = $this->referenceStore->getAll();

        self::assertContains('pages-123', $tags);
        self::assertCount(1, $tags);
    }

    public function testAddDifferentResourceKeys(): void
    {
        $this->referenceStore->add('123', 'pages');
        $this->referenceStore->add('123', 'articles'); // Same ID, different resource key

        $tags = $this->referenceStore->getAll();

        self::assertContains('pages-123', $tags);
        self::assertContains('articles-123', $tags);
        self::assertCount(2, $tags);
    }

    public function testGetAllEmpty(): void
    {
        $tags = $this->referenceStore->getAll();

        self::assertEmpty($tags);
    }

    public function testReset(): void
    {
        $this->referenceStore->add('123', 'pages');
        $this->referenceStore->add('456', 'articles');

        self::assertCount(2, $this->referenceStore->getAll());

        $this->referenceStore->reset();

        self::assertEmpty($this->referenceStore->getAll());
    }

    public function testMixedUuidAndNonUuid(): void
    {
        $uuid = Uuid::v7()->toRfc4122();

        $this->referenceStore->add($uuid, 'pages');
        $this->referenceStore->add('123', 'pages');
        $this->referenceStore->add('456', 'articles');

        $tags = $this->referenceStore->getAll();

        self::assertContains($uuid, $tags);
        self::assertContains('pages-123', $tags);
        self::assertContains('articles-456', $tags);
        self::assertCount(3, $tags);
    }
}
