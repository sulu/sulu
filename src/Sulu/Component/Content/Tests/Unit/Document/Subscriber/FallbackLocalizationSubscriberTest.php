<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\LocalizationFinder;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Subscriber\FallbackLocalizationSubscriber;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class FallbackLocalizationSubscriberTest extends SubscriberTestCase
{
    const FIX_LOCALE = 'en';
    const FIX_WEBSPACE = 'sulu_io';

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentRegistry
     */
    private $registry;

    /**
     * @var StructureBehavior
     */
    private $document;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var Localization
     */
    private $localization1;

    /**
     * @var Localization
     */
    private $localization2;

    /**
     * @var FallbackLocalizationSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->registry = $this->prophesize(DocumentRegistry::class);

        $this->document = $this->prophesize(StructureBehavior::class)->willImplement(WebspaceBehavior::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->localization1 = $this->prophesize(Localization::class);
        $this->localization2 = $this->prophesize(Localization::class);

        $this->subscriber = new FallbackLocalizationSubscriber(
            $this->encoder->reveal(),
            $this->inspector->reveal(),
            $this->registry->reveal(),
            new LocalizationFinder($this->webspaceManager->reveal())
        );

        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getLocale()->willReturn(self::FIX_LOCALE);
        $this->webspaceManager->findWebspaceByKey(self::FIX_WEBSPACE)->willReturn($this->webspace);
        $this->registry->getDefaultLocale()->willReturn('de');
    }

    /**
     * It should return early if not implementing StructureBehavior.
     */
    public function testReturnEarly()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    public function testAvailableLocale()
    {
        $this->inspector->getLocales($this->document)->willReturn(['de', 'en']);
        $this->hydrateEvent->setLocale('en')->shouldBeCalled();
        $this->hydrateEvent->getOption('load_ghost_content', true)->willReturn(true);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * If no webspace is available, then return the first available localization for the document.
     */
    public function testNoWebspace()
    {
        $this->inspector->getWebspace($this->document->reveal())->willReturn(null);
        $this->inspector->getLocales($this->document)->willReturn(['de', 'fr']);
        $this->hydrateEvent->setLocale('de')->shouldBeCalled();
        $this->hydrateEvent->getOption('load_ghost_content', true)->willReturn(true);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should reset the original locale if the load_ghost_content option is false.
     */
    public function testNoWebspaceDoNotLoadGhostContent()
    {
        $this->inspector->getWebspace($this->document->reveal())->willReturn(null);
        $this->inspector->getLocales($this->document)->willReturn(['de', 'fr']);
        $this->hydrateEvent->setLocale('de')->shouldNotBeCalled();

        $this->hydrateEvent->getOption('load_ghost_content', true)->willReturn(false);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should throw an exception if no locale can be determined.
     *
     * @expectedException RuntimeException
     */
    public function testNoLocale()
    {
        $this->inspector->getWebspace($this->document->reveal())->willReturn(null);
        $this->inspector->getLocales($this->document)->willReturn([]);
        $this->node->getPath()->willReturn('/path/to');
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should return webspace parent localization.
     */
    public function testWebspaceParentLocalization()
    {
        $this->inspector->getWebspace($this->document->reveal())->willReturn(self::FIX_WEBSPACE);
        $this->inspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->webspace->getLocalization(self::FIX_LOCALE)->willReturn($this->localization1->reveal());
        $this->localization1->getLocale()->willReturn('en');
        $this->localization2->getLocale()->willReturn('de');
        $this->localization1->getParent()->willReturn($this->localization2->reveal());
        $this->hydrateEvent->getOption('load_ghost_content', true)->willReturn(true);

        $this->hydrateEvent->setLocale('de')->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should return children localizations.
     */
    public function testWebspaceChildrenLocalization()
    {
        $this->inspector->getWebspace($this->document->reveal())->willReturn(self::FIX_WEBSPACE);
        $this->inspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->webspace->getLocalization(self::FIX_LOCALE)->willReturn($this->localization1->reveal());

        $this->localization1->getLocale()->willReturn('en');
        $this->localization2->getLocale()->willReturn('de');
        $this->hydrateEvent->getOption('load_ghost_content', true)->willReturn(true);

        $this->localization1->getParent()->willReturn(null);
        $this->localization1->getChildren()->willReturn([
            $this->localization2->reveal(),
        ]);

        $this->hydrateEvent->setLocale('de')->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should return any localizations if neither parent nor children.
     */
    public function testWebspaceAnyLocalization()
    {
        $this->inspector->getWebspace($this->document->reveal())->willReturn(self::FIX_WEBSPACE);
        $this->inspector->getLocales($this->document->reveal())->willReturn(['de']);
        $this->webspace->getLocalization(self::FIX_LOCALE)->willReturn($this->localization1->reveal());

        $this->localization1->getLocale()->willReturn('en');
        $this->localization2->getLocale()->willReturn('de');

        $this->hydrateEvent->getOption('load_ghost_content', true)->willReturn(true);

        $this->localization1->getParent()->willReturn(null);
        $this->localization1->getChildren()->willReturn([]);

        $this->webspace->getLocalizations()->willReturn([
            $this->localization2->reveal(),
        ]);

        $this->hydrateEvent->setLocale('de')->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
