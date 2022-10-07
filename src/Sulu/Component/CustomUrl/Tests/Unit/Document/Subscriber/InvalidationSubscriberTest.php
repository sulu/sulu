<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Document\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Document\Subscriber\InvalidationSubscriber;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class InvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    public function testInvalidateDocumentBeforePublishing(): void
    {
        $customUrlManager = $this->prophesize(CustomUrlManagerInterface::class);
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $cacheManager = $this->prophesize(CacheManager::class);
        $requestStack = $this->prophesize(RequestStack::class);

        $subscriber = new InvalidationSubscriber(
            $customUrlManager->reveal(),
            $documentInspector->reveal(),
            $cacheManager->reveal(),
            $requestStack->reveal()
        );

        $routeDocument1 = $this->prophesize(RouteDocument::class);
        $routeDocument1->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-1');

        $routeDocument2 = $this->prophesize(RouteDocument::class);
        $routeDocument2->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-2');

        $document = $this->prophesize(BasePageDocument::class);

        $customUrl = $this->prophesize(CustomUrlDocument::class);
        $customUrl->getRoutes()->willReturn(
            ['sulu.lo/test-1' => $routeDocument1->reveal(), 'sulu.lo/test-2' => $routeDocument2->reveal()]
        );

        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $customUrlManager->findByPage($document->reveal())->willReturn([$customUrl->reveal()]);
        $cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();
        $cacheManager->invalidatePath('http://sulu.lo/test-2')->shouldBeCalled();

        $subscriber->invalidateDocumentBeforePublishing($event->reveal());
    }

    public function testInvalidateDocumentBeforePublishingOfOtherDocument(): void
    {
        $customUrlManager = $this->prophesize(CustomUrlManagerInterface::class);
        $documentInspector = $this->prophesize(DocumentInspector::class);
        $cacheManager = $this->prophesize(CacheManager::class);
        $requestStack = $this->prophesize(RequestStack::class);

        $subscriber = new InvalidationSubscriber(
            $customUrlManager->reveal(),
            $documentInspector->reveal(),
            $cacheManager->reveal(),
            $requestStack->reveal()
        );

        $document = new \stdClass();
        $event = $this->prophesize(PublishEvent::class);
        $event->getDocument()->willReturn($document);

        $customUrlManager->findByPage(Argument::any())->shouldNotBeCalled();
        $cacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();

        $subscriber->invalidateDocumentBeforePublishing($event->reveal());
    }
}
