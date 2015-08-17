<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;

class NavigationContextSubscriberTest extends SubscriberTestCase
{
    /**
     * @var NavigationContextSubscriber
     */
    private $subscriber;

    /**
     * @var NavigationContextBehavior
     */
    private $document;

    public function setUp()
    {
        parent::setUp();

        $this->subscriber = new NavigationContextSubscriber($this->encoder->reveal());
        $this->document = $this->prophesize(NavigationContextBehavior::class);
        $this->encoder->localizedSystemName('navContexts', 'en')->willReturn('i18n:en-navContexts');
    }

    public function testPersistLocaleIsNull()
    {
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getLocale()->willReturn(null);
        $this->node->setProperty()->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testPersist()
    {
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getLocale()->willReturn('en');

        $this->document->getNavigationContexts()->willReturn(['main']);
        $this->node->setProperty('i18n:en-navContexts', ['main'])->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHydrate()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document->reveal());
        $this->hydrateEvent->getNode()->willReturn($this->node);
        $this->hydrateEvent->getLocale()->willReturn('en');

        $this->node->getPropertyValueWithDefault('i18n:en-navContexts', [])->willReturn(['main']);

        $this->subscriber->handleHydrate($this->hydrateEvent->reveal());
    }
}
