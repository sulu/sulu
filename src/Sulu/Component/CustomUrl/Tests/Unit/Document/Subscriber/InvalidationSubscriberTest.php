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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrl;
use Sulu\Bundle\CustomUrlBundle\Entity\CustomUrlRoute;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\CustomUrl\Document\Subscriber\InvalidationSubscriber;
use Sulu\Component\CustomUrl\Repository\CustomUrlRepositoryInterface;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class InvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CustomUrlRepositoryInterface>
     */
    private ObjectProphecy $customUrlRepository;

    /**
     * @var ObjectProphecy<CacheManager>
     */
    private ObjectProphecy $cacheManager;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private ObjectProphecy $requestStack;

    private InvalidationSubscriber $subscriber;

    public function setUp(): void
    {
        $this->customUrlRepository = $this->prophesize(CustomUrlRepositoryInterface::class);
        $this->cacheManager = $this->prophesize(CacheManager::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->subscriber = new InvalidationSubscriber(
            $this->customUrlRepository->reveal(),
            $this->cacheManager->reveal(),
            $this->requestStack->reveal()
        );
    }

    public function testInvalidateDocumentBeforePublishing(): void
    {
        $customUrl = new CustomUrl();
        $routeDocument1 = new CustomUrlRoute($customUrl, '/cmf/sulu_io/custom-urls/routes/sulu.lo/test-1');
        $routeDocument2 = new CustomUrlRoute($customUrl, '/cmf/sulu_io/custom-urls/routes/sulu.lo/test-2');
        $customUrl->addRoute($routeDocument1);
        $customUrl->addRoute($routeDocument2);

        $document = $this->prophesize(BasePageDocument::class);

        $this->customUrlRepository->findByPage($document->reveal())->willReturn([$customUrl]);

        $this->cacheManager->invalidatePath('http://sulu.lo/test-1')->shouldBeCalled();
        $this->cacheManager->invalidatePath('http://sulu.lo/test-2')->shouldBeCalled();

        $event = new PublishEvent($document->reveal(), 'de');
        $this->subscriber->invalidateDocumentBeforePublishing($event);
    }
}
