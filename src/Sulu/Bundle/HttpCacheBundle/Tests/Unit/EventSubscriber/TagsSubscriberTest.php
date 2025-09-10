<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\EventSubscriber;

use FOS\HttpCacheBundle\Http\SymfonyResponseTagger;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\HttpCacheBundle\EventSubscriber\TagsSubscriber;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;

class TagsSubscriberTest extends TestCase
{
    private TagsSubscriber $tagsSubscriber;
    private ReferenceStore $referenceStore;
    private MockSymfonyResponseTagger $symfonyResponseTagger;

    protected function setUp(): void
    {
        $this->referenceStore = new ReferenceStore();
        $this->symfonyResponseTagger = new MockSymfonyResponseTagger();

        $this->tagsSubscriber = new TagsSubscriber(
            $this->referenceStore,
            $this->symfonyResponseTagger
        );
    }

    public function testAddTagsWithTags(): void
    {
        $this->referenceStore->add('1', 'test');
        $this->referenceStore->add('2', 'test');
        $this->referenceStore->add('123', 'page');
        $this->referenceStore->add('example', 'webspace');

        $expectedTags = [
            'test-1' => 'test-1',
            'test-2' => 'test-2',
            'page-123' => 'page-123',
            'webspace-example' => 'webspace-example',
        ];

        $this->tagsSubscriber->addTags();

        self::assertEquals($expectedTags, $this->symfonyResponseTagger->addedTags);
    }

    public function testAddTagsWithEmptyTags(): void
    {
        $this->tagsSubscriber->addTags();

        self::assertNull($this->symfonyResponseTagger->addedTags);
    }

    public function testAddTagsWithUuidTags(): void
    {
        $uuid1 = '550e8400-e29b-41d4-a716-446655440000';
        $uuid2 = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        $this->referenceStore->add($uuid1, 'pages');
        $this->referenceStore->add($uuid2, 'articles');

        $expectedTags = [
            $uuid1 => $uuid1,
            $uuid2 => $uuid2,
        ];

        $this->tagsSubscriber->addTags();

        self::assertEquals($expectedTags, $this->symfonyResponseTagger->addedTags);
    }

    public function testAddTagsWithMixedTags(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $this->referenceStore->add($uuid, 'pages');
        $this->referenceStore->add('123', 'articles');
        $this->referenceStore->add('test', 'webspace');

        $expectedTags = [
            $uuid => $uuid,
            'articles-123' => 'articles-123',
            'webspace-test' => 'webspace-test',
        ];

        $this->tagsSubscriber->addTags();

        self::assertEquals($expectedTags, $this->symfonyResponseTagger->addedTags);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = TagsSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('kernel.response', $events);
        self::assertEquals(['addTags', 1024], $events['kernel.response']);
    }
}

class MockSymfonyResponseTagger extends SymfonyResponseTagger
{
    /**
     * @var string[]|null
     */
    public ?array $addedTags = null;

    public function __construct()
    {
        // Don't call parent constructor to avoid complex setup
    }

    /**
     * @param string[] $tags
     */
    public function addTags(array $tags): static
    {
        $this->addedTags = $tags;

        return $this;
    }
}
