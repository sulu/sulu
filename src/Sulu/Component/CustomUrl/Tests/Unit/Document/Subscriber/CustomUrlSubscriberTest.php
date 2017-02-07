<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Document\Subscriber\CustomUrlSubscriber;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class CustomUrlSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomUrlSubscriber
     */
    private $customUrlSubscriber;

    /**
     * @var GeneratorInterface
     */
    private $generator;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function setUp()
    {
        $this->generator = $this->prophesize(GeneratorInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->pathBuilder = $this->prophesize(PathBuilder::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->customUrlSubscriber = new CustomUrlSubscriber(
            $this->generator->reveal(),
            $this->documentManager->reveal(),
            $this->pathBuilder->reveal(),
            $this->inspector->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    public function testHandleRemove()
    {
        $removeEvent = $this->prophesize(RemoveEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $removeEvent->getDocument()->willReturn($document->reveal());

        $routeDocument = $this->prophesize(RouteBehavior::class);

        $this->inspector->getReferrers($document->reveal())->willReturn([$routeDocument->reveal()]);

        $this->customUrlSubscriber->handleRemove($removeEvent->reveal());

        $this->documentManager->remove($routeDocument->reveal())->shouldBeCalled();
    }

    public function testHandleHydrate()
    {
        $hydrateEvent = $this->prophesize(HydrateEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $hydrateEvent->getDocument()->willReturn($document->reveal());

        $routeDocument1 = $this->prophesize(RouteDocument::class);
        $routeDocument1->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-1');
        $routeDocument2 = $this->prophesize(RouteDocument::class);
        $routeDocument2->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-2');

        $this->inspector->getWebspace($document->reveal())->willReturn('sulu_io');
        $document->setRoutes(
            ['sulu.lo/test-1' => $routeDocument1->reveal(), 'sulu.lo/test-2' => $routeDocument2->reveal()]
        )->shouldBeCalled();

        $this->inspector->getReferrers($document->reveal())->willReturn([$routeDocument1->reveal()]);
        $this->inspector->getReferrers($routeDocument1->reveal())->willReturn([$routeDocument2->reveal()]);
        $this->inspector->getReferrers($routeDocument2->reveal())->willReturn([]);
        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom-urls/routes');

        $this->customUrlSubscriber->handleHydrate($hydrateEvent->reveal());
    }

    public function testHandlePersist()
    {
        $persistEvent = $this->prophesize(PersistEvent::class);
        $document = $this->prophesize(CustomUrlDocument::class);
        $persistEvent->getDocument()->willReturn($document->reveal());
        $persistEvent->getLocale()->willReturn('de');

        $routeDocument1 = $this->prophesize(RouteDocument::class);
        $routeDocument1->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-1');
        $routeDocument2 = $this->prophesize(RouteDocument::class);
        $routeDocument2->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-2');
        $routeDocument3 = $this->prophesize(RouteDocument::class);
        $routeDocument3->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-3');

        $document->getRoutes()->willReturn(
            ['sulu.lo/test-1' => $routeDocument1->reveal(), 'sulu.lo/test-2' => $routeDocument2->reveal()]
        );

        $this->inspector->getWebspace($document->reveal())->willReturn('sulu_io');
        $this->pathBuilder->build(['%base%', 'sulu_io', '%custom_urls%', '%custom_urls_routes%'])
            ->willReturn('/cmf/sulu_io/custom-urls/routes');

        $document->getBaseDomain()->willReturn('sulu.lo/*');
        $document->getDomainParts()->willReturn(['prefix' => '', 'suffix' => ['test-3']]);
        $this->generator->generate('sulu.lo/*', ['prefix' => '', 'suffix' => ['test-3']])->willReturn('sulu.lo/test-3');

        $webspace = new Webspace();
        $webspace->addLocalization(new Localization('de'));

        $document->getTargetLocale()->willReturn('de');

        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        $this->documentManager->find('/cmf/sulu_io/custom-urls/routes/sulu.lo/test-3', 'de')
            ->willThrow(new DocumentNotFoundException());
        $this->documentManager->create('custom_url_route')->willReturn($routeDocument3->reveal());
        $routeDocument3->setTargetDocument($document->reveal())->shouldBeCalled();
        $routeDocument3->setLocale('de')->shouldBeCalled();
        $routeDocument3->setHistory(false)->shouldBeCalled();

        $this->documentManager->persist(
            $routeDocument3->reveal(),
            'de',
            [
                'path' => '/cmf/sulu_io/custom-urls/routes/sulu.lo/test-3',
                'auto_create' => true,
            ]
        )->shouldBeCalled();
        $this->documentManager->publish($routeDocument3->reveal(), 'de')->shouldBeCalled();
        $this->documentManager->persist(
            $routeDocument2->reveal(),
            'de',
            [
                'path' => '/cmf/sulu_io/custom-urls/routes/sulu.lo/test-2',
                'auto_create' => true,
            ]
        )->shouldBeCalled();
        $this->documentManager->publish($routeDocument2->reveal(), 'de')->shouldBeCalled();
        $this->documentManager->persist(
            $routeDocument1->reveal(),
            'de',
            [
                'path' => '/cmf/sulu_io/custom-urls/routes/sulu.lo/test-1',
                'auto_create' => true,
            ]
        )->shouldBeCalled();
        $this->documentManager->publish($routeDocument1->reveal(), 'de')->shouldBeCalled();

        $document->addRoute('sulu.lo/test-3', $routeDocument3->reveal())->shouldBecalled();

        $routeDocument1->setTargetDocument($routeDocument3->reveal())->shouldBeCalled();
        $routeDocument2->setTargetDocument($routeDocument3->reveal())->shouldBeCalled();
        $routeDocument1->setHistory(true)->shouldBeCalled();
        $routeDocument2->setHistory(true)->shouldBeCalled();

        $this->customUrlSubscriber->handlePersist($persistEvent->reveal());
    }
}
