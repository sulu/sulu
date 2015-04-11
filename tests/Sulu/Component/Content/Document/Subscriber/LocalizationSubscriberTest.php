<?php

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Prophecy\Argument;

class LocalizationSubscriberTest extends SubscriberTestCase
{
    const FIX_LOCALE = 'en';
    const FIX_PROPERTY_NAME = 'property-name';

    public function setUp()
    {
        parent::setUp();
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->registry = $this->prophesize(DocumentRegistry::class);

        $this->document = $this->prophesize(ContentBehavior::class);

        $this->subscriber = new LocalizationSubscriber(
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
     * It should not re-register the document if the resolved locale is the same as the given locale
     */
    public function testNoReRegister()
    {
        $this->node->hasProperty(self::FIX_PROPERTY_NAME)->willReturn(true);
        $this->registry->registerDocument(Argument::cetera())->shouldNotBeCalled();
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
        $this->registry->registerDocument(
            $this->document->reveal(),
            $this->node->reveal(),
            'de'
        )->shouldBeCalled();
        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
