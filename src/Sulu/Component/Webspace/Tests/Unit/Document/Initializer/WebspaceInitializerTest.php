<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Document\Initializer;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PageBundle\Document\HomeDocument;
use Sulu\Bundle\PageBundle\Document\RouteDocument;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Console\Output\Output;

class WebspaceInitializerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<PathBuilder>
     */
    private $pathBuilder;

    /**
     * @var ObjectProphecy<NodeManager>
     */
    private $nodeManager;

    /**
     * @var WebspaceInitializer
     */
    private $webspaceInitializer;

    /**
     * @var ObjectProphecy<Output>
     */
    private $output;

    /**
     * @var ObjectProphecy<WebspaceCollection>
     */
    private $webspaceCollection;

    public function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->pathBuilder = $this->prophesize(PathBuilder::class);
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->output = $this->prophesize(Output::class);
        $this->webspaceCollection = $this->prophesize(WebspaceCollection::class);

        $this->webspaceManager->getWebspaceCollection()->willReturn($this->webspaceCollection->reveal());

        $this->webspaceInitializer = new WebspaceInitializer(
            $this->webspaceManager->reveal(),
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->pathBuilder->reveal(),
            $this->nodeManager->reveal()
        );
    }

    public function testInitialize(): void
    {
        /** @var Webspace $webspace1 */
        $webspace1 = new Webspace();
        $webspace1->setKey('webspace1');
        $webspace1->setTheme('theme1');
        $localization1_1 = new Localization();
        $localization1_1->setLanguage('de');
        $localization1_2 = new Localization();
        $localization1_2->setLanguage('en');
        $webspace1->setLocalizations([$localization1_1, $localization1_2]);

        /** @var Webspace $webspace2 */
        $webspace2 = new Webspace();
        $webspace2->setKey('webspace2');
        $webspace2->setTheme('theme1');
        $localization2_1 = new Localization();
        $localization2_1->setLanguage('de');
        $webspace2->setLocalizations([$localization2_1]);

        $this->webspaceCollection->getIterator()->willReturn(
            new \ArrayIterator([$webspace1, $webspace2])
        );

        $this->pathBuilder->build(['%base%', 'webspace1', '%content%'])->willReturn('/cmf/webspace1/contents');
        $this->pathBuilder->build(['%base%', 'webspace2', '%content%'])->willReturn('/cmf/webspace2/contents');
        $this->pathBuilder->build(['%base%', 'webspace1', '%route%'])->willReturn('/cmf/webspace1/routes');
        $this->pathBuilder->build(['%base%', 'webspace2', '%route%'])->willReturn('/cmf/webspace2/routes');

        $routeDocument = $this->prophesize(RouteDocument::class);
        $this->documentManager->create('route')->willReturn($routeDocument->reveal());

        $homeDocument = $this->prophesize(HomeDocument::class);
        $this->documentManager->create('home')->willReturn($homeDocument->reveal());
        $this->documentManager->find(
            '/cmf/webspace1/contents',
            'en',
            ['load_ghost_content' => false]
        )->willReturn($homeDocument->reveal());
        $this->documentManager->find(Argument::cetera())->willThrow(DocumentNotFoundException::class);

        $this->documentManager->persist(
            $homeDocument->reveal(),
            'de',
            [
                'path' => '/cmf/webspace1/contents',
                'auto_create' => true,
                'ignore_required' => true,
            ]
        )->shouldBeCalled();
        $this->documentManager->persist(
            $homeDocument->reveal(),
            'en',
            [
                'ignore_required' => true,
            ]
        )->shouldBeCalled();
        $this->documentManager->persist(
            $homeDocument->reveal(),
            'de',
            [
                'path' => '/cmf/webspace2/contents',
                'auto_create' => true,
                'ignore_required' => true,
            ]
        )->shouldBeCalled();

        $this->documentManager->publish($homeDocument->reveal(), 'de', ['ignore_required' => true])->shouldBeCalledTimes(2);
        $this->documentManager->publish($homeDocument->reveal(), 'en', ['ignore_required' => true])->shouldBeCalledTimes(1);

        $this->documentManager->persist(
            $routeDocument->reveal(),
            'de',
            ['path' => '/cmf/webspace1/routes/de', 'auto_create' => true]
        )->shouldBeCalled();
        $this->documentManager->persist(
            $routeDocument->reveal(),
            'en',
            ['path' => '/cmf/webspace1/routes/en', 'auto_create' => true]
        )->shouldBeCalled();
        $this->documentManager->persist(
            $routeDocument->reveal(),
            'de',
            ['path' => '/cmf/webspace2/routes/de', 'auto_create' => true]
        )->shouldBeCalled();

        $this->documentManager->publish($routeDocument->reveal(), 'de')->shouldBeCalledTimes(2);
        $this->documentManager->publish($routeDocument->reveal(), 'en')->shouldBeCalledTimes(1);

        $this->documentManager->flush()->shouldBeCalled();

        $this->webspaceInitializer->initialize($this->output->reveal());
    }
}
