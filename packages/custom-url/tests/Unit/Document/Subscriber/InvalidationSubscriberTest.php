<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Tests\Unit\Document\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRouteRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\EventSubscriber\CacheInvalidationSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<CustomUrlRepositoryInterface>
     */
    private ObjectProphecy $customUrlRepository;

    /**
     * @var ObjectProphecy<CustomUrlRouteRepositoryInterface>
     */
    private ObjectProphecy $customUrlRouteRepository;

    /**
     * @var ObjectProphecy<CacheManager>
     */
    private ObjectProphecy $cacheManager;

    private CacheInvalidationSubscriber $subscriber;

    public function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->customUrlRouteRepository = $this->prophesize(CustomUrlRouteRepositoryInterface::class);
        $this->cacheManager = $this->prophesize(CacheManager::class);

        $requestStack = $this->prophesize(RequestStack::class);
        $requestStack->getCurrentRequest()->willReturn(Request::create('http://sulu.lo/'));

        $this->subscriber = new CacheInvalidationSubscriber(
            $this->customUrlRepository->reveal(),
            $this->customUrlRouteRepository->reveal(),
            $this->cacheManager->reveal(),
            $requestStack->reveal()
        );
    }

    public function testInvalidateDocumentBeforePublishing(): void
    {
        $customUrl = new CustomUrl();
        $this->customUrlRouteRepository->findByCustomUrl($customUrl)->willReturn([
            new CustomUrlRoute($customUrl, 'sulu.lo/test-1'),
            new CustomUrlRoute($customUrl, 'sulu.lo/test-2'),
        ]);

        $document = $this->prophesize(BasePageDocument::class);

        $this->customUrlRepository
            ->findByTarget($document->reveal())
            ->shouldBeCalled()
            ->willReturn([$customUrl]);

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();
        $this->cacheManager->invalidatePath('http://sulu.lo/test-2')->shouldBeCalled();

        $event = new PublishEvent($document->reveal(), 'de');
        $this->subscriber->invalidateDocument($event);
    }

    public function testInvalidateCustomUrl(): void
    {
        $customUrl = new CustomUrl();
        $this->customUrlRouteRepository->findByCustomUrl($customUrl)->willReturn([
            new CustomUrlRoute($customUrl, 'sulu.lo/test-1'),
            new CustomUrlRoute($customUrl, 'sulu.lo/test-2'),
        ]);

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();
        $this->cacheManager->invalidatePath('http://sulu.lo/test-2')->shouldBeCalled();

        $event = new PublishEvent($customUrl, 'de');
        $this->subscriber->invalidateDocument($event);
    }

    public function testInvalidateCustomUrlRoute(): void
    {
        $route = new CustomUrlRoute(new CustomUrl(), 'sulu.lo/test-1');

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();

        $event = new PublishEvent($route, 'de');
        $this->subscriber->invalidateDocument($event);
    }
}
