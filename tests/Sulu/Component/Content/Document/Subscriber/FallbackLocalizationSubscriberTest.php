<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Prophecy\Argument;
use Sulu\Component\Webspace\Webspace;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;

class FallbackLocalizationSubscriberTest extends SubscriberTestCase
{
    const FIX_LOCALE = 'en';
    const FIX_PROPERTY_NAME = 'property-name';
    const FIX_WEBSPACE = 'sulu_io';

    public function setUp()
    {
        parent::setUp();
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->registry = $this->prophesize(DocumentRegistry::class);

        $this->document = $this->prophesize(ContentBehavior::class)->willImplement(WebspaceBehavior::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->localization1 = $this->prophesize(Localization::class);
        $this->localization2 = $this->prophesize(Localization::class);

        $this->subscriber = new FallbackLocalizationSubscriber(
            $this->encoder->reveal(),
            $this->webspaceManager->reveal(),
            $this->inspector->reveal(),
            $this->registry->reveal()
        );

        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getLocale()->willReturn(self::FIX_LOCALE);
        $this->encoder->localizedSystemName(
            ContentSubscriber::STRUCTURE_TYPE_FIELD, self::FIX_LOCALE
        )->willReturn(self::FIX_PROPERTY_NAME);
        $this->webspaceManager->findWebspaceByKey(self::FIX_WEBSPACE)->willReturn($this->webspace);
        $this->registry->getDefaultLocale()->willReturn('de');
    }

    /**
     * It should return early if not implementing ContentBehavior
     */
    public function testReturnEarly()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * If no webspace is available, then return the first available localization for the document
     */
    public function testNoWebspace()
    {
        $this->node->hasProperty(self::FIX_PROPERTY_NAME)->willReturn(false);
        $this->inspector->getWebspace($this->document->reveal())->willReturn(null);
        $this->inspector->getLocales($this->document)->willReturn(array('de', 'fr'));
        $this->registry->updateLocale(
            $this->document->reveal(),
            'de',
            'en'
        )->shouldBeCalled();
        $this->hydrateEvent->setLocale('de')->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should throw an exception if no locale can be determined
     *
     * @expectedException RuntimeException
     */
    public function testNoLocale()
    {
        $this->node->hasProperty(self::FIX_PROPERTY_NAME)->willReturn(false);
        $this->inspector->getWebspace($this->document->reveal())->willReturn(null);
        $this->inspector->getLocales($this->document)->willReturn(array());
        $this->node->getPath()->willReturn('/path/to');
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should return webspace parent localization
     */
    public function testWebspaceParentLocalization()
    {
        $this->node->hasProperty(self::FIX_PROPERTY_NAME)->willReturn(false);
        $this->inspector->getWebspace($this->document->reveal())->willReturn(self::FIX_WEBSPACE);
        $this->webspace->getLocalization(self::FIX_LOCALE)->willReturn($this->localization1->reveal());
        $this->localization1->getLocalization()->willReturn('en');
        $this->localization2->getLocalization()->willReturn('at');
        $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, 'en')->willReturn('prop1');
        $this->node->hasProperty('prop1')->willReturn(false);
        $this->localization1->getParent()->willReturn($this->localization2->reveal());
        $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, 'at')->willReturn('prop2');
        $this->node->hasProperty('prop2')->willReturn(true);

        $this->registry->updateLocale(
            $this->document->reveal(),
            'at',
            'en'
        )->shouldBeCalled();
        $this->hydrateEvent->setLocale('at')->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should return children localizations
     */
    public function testWebspaceChildrenLocalization()
    {
        $this->node->hasProperty(self::FIX_PROPERTY_NAME)->willReturn(false);
        $this->inspector->getWebspace($this->document->reveal())->willReturn(self::FIX_WEBSPACE);
        $this->webspace->getLocalization(self::FIX_LOCALE)->willReturn($this->localization1->reveal());

        $this->localization1->getLocalization()->willReturn('en');
        $this->localization2->getLocalization()->willReturn('at');

        $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, 'en')->willReturn('prop1');
        $this->node->hasProperty('prop1')->willReturn(false);
        $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, 'at')->willReturn('prop2');
        $this->node->hasProperty('prop2')->willReturn(true);

        $this->localization1->getParent()->willReturn(null);
        $this->localization1->getChildren()->willReturn(array(
            $this->localization2->reveal()
        ));

        $this->registry->updateLocale(
            $this->document->reveal(),
            'at',
            'en'
        )->shouldBeCalled();
        $this->hydrateEvent->setLocale('at')->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }

    /**
     * It should return any localizations if neither parent nor children
     */
    public function testWebspaceAnyLocalization()
    {
        $this->node->hasProperty(self::FIX_PROPERTY_NAME)->willReturn(false);
        $this->inspector->getWebspace($this->document->reveal())->willReturn(self::FIX_WEBSPACE);
        $this->webspace->getLocalization(self::FIX_LOCALE)->willReturn($this->localization1->reveal());

        $this->localization1->getLocalization()->willReturn('en');
        $this->localization2->getLocalization()->willReturn('at');

        $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, 'en')->willReturn('prop1');
        $this->node->hasProperty('prop1')->willReturn(false);
        $this->encoder->localizedSystemName(ContentSubscriber::STRUCTURE_TYPE_FIELD, 'at')->willReturn('prop2');
        $this->node->hasProperty('prop2')->willReturn(true);

        $this->localization1->getParent()->willReturn(null);
        $this->localization1->getChildren()->willReturn(array());

        $this->webspace->getLocalizations()->willReturn(array(
            $this->localization2->reveal()
        ));

        $this->registry->updateLocale(
            $this->document->reveal(),
            'at',
            'en'
        )->shouldBeCalled();
        $this->hydrateEvent->setLocale('at')->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
