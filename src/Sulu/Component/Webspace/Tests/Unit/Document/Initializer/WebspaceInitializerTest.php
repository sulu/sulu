<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Document\Initializer;

use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
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

class WebspaceInitializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var WebspaceInitializer
     */
    private $webspaceInitializer;

    /**
     * @var Output
     */
    private $output;

    /**
     * @var WebspaceCollection
     */
    private $webspaceCollection;

    public function setUp()
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

    public function testInitialize()
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

        $routeNode = $this->prophesize(RouteDocument::class);
        $this->documentManager->create('route')->willReturn($routeNode->reveal());

        $this->documentManager->find(
            '/cmf/webspace1/contents',
            'en',
            ['load_ghost_content' => false]
        )->willReturn(new HomeDocument());
        $this->documentManager->find(Argument::cetera())->willThrow(DocumentNotFoundException::class);

        $this->documentManager->persist(
            Argument::type(HomeDocument::class),
            'de',
            [
                'path' => '/cmf/webspace1/contents',
                'auto_create' => true,
                'ignore_required' => true,
            ]
        )->shouldBeCalled();
        $this->documentManager->persist(
            Argument::type(HomeDocument::class),
            'en',
            [
                'ignore_required' => true,
            ]
        )->shouldBeCalled();
        $this->documentManager->persist(
            Argument::type(HomeDocument::class),
            'de',
            [
                'path' => '/cmf/webspace2/contents',
                'auto_create' => true,
                'ignore_required' => true,
            ]
        )->shouldBeCalled();

        $this->documentManager->publish(Argument::type(HomeDocument::class), 'de')->shouldBeCalledTimes(2);
        $this->documentManager->publish(Argument::type(HomeDocument::class), 'en')->shouldBeCalledTimes(1);

        $this->documentManager->persist(
            Argument::type(RouteDocument::class),
            'de',
            ['path' => '/cmf/webspace1/routes/de', 'auto_create' => true]
        )->shouldBeCalled();
        $this->documentManager->persist(
            Argument::type(RouteDocument::class),
            'en',
            ['path' => '/cmf/webspace1/routes/en', 'auto_create' => true]
        )->shouldBeCalled();
        $this->documentManager->persist(
            Argument::type(RouteDocument::class),
            'de',
            ['path' => '/cmf/webspace2/routes/de', 'auto_create' => true]
        )->shouldBeCalled();

        $this->documentManager->publish(Argument::type(RouteDocument::class), 'de')->shouldBeCalledTimes(2);
        $this->documentManager->publish(Argument::type(RouteDocument::class), 'en')->shouldBeCalledTimes(1);

        $this->documentManager->flush()->shouldBeCalled();

        $this->webspaceInitializer->initialize($this->output->reveal());
    }
}
