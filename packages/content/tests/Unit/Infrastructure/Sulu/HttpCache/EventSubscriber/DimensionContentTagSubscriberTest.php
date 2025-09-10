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

namespace Sulu\Content\Tests\Unit\Infrastructure\Sulu\HttpCache\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Sulu\Content\Infrastructure\Sulu\HttpCache\EventSubscriber\DimensionContentTagSubscriber;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DimensionContentTagSubscriberTest extends TestCase
{
    private DimensionContentTagSubscriber $dimensionContentTagSubscriber;
    private ReferenceStore $referenceStore;
    private RequestStack $requestStack;
    private Request $request;

    protected function setUp(): void
    {
        $this->referenceStore = new ReferenceStore();
        $this->requestStack = new RequestStack();
        $this->request = new Request();
        $this->requestStack->push($this->request);

        $this->dimensionContentTagSubscriber = new DimensionContentTagSubscriber(
            $this->referenceStore,
            $this->requestStack
        );
    }

    public function testAddTag(): void
    {
        // Create real Example entity
        $example = new Example();
        $example->id = 123;

        // Create real ExampleDimensionContent
        $dimensionContent = new ExampleDimensionContent($example);

        // Set the object in request attributes
        $this->request->attributes->set('object', $dimensionContent);

        // Call the method
        $this->dimensionContentTagSubscriber->addTag();

        $tags = $this->referenceStore->getAll();
        self::assertCount(1, $tags);
        self::assertContains('examples-123', $tags);
    }

    public function testAddTagWithoutRequest(): void
    {
        $this->requestStack->pop(); // Remove the request

        $this->dimensionContentTagSubscriber->addTag();

        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testAddTagWithoutObject(): void
    {
        $this->dimensionContentTagSubscriber->addTag();

        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testAddTagWithWrongObjectType(): void
    {
        $this->request->attributes->set('object', new \stdClass());

        $this->dimensionContentTagSubscriber->addTag();

        $tags = $this->referenceStore->getAll();
        self::assertEmpty($tags);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = DimensionContentTagSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('kernel.response', $events);
        self::assertEquals(['addTag', 2048], $events['kernel.response']);
    }
}
