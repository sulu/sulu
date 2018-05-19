<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\LocaleSubscriber;

class LocaleSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new TestLocaleDocument();
        $this->accessor = new DocumentAccessor($this->document);
        $this->registry = $this->prophesize(DocumentRegistry::class);

        $this->subscriber = new LocaleSubscriber(
            $this->registry->reveal()
        );
    }

    /**
     * It should return early when not implementing.
     */
    public function testHydrateNotImplementing()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing);
        $this->subscriber->handleLocale($this->hydrateEvent->reveal());
    }

    /**
     * It should set the node name on the document.
     */
    public function testHydrate()
    {
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
        $this->registry->getLocaleForDocument($this->document)->willReturn('fr');

        $this->subscriber->handleLocale($this->hydrateEvent->reveal());

        $this->assertEquals('fr', $this->document->getLocale());
    }
}

class TestLocaleDocument implements LocaleBehavior
{
    private $locale;

    private $originalLocale;

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getOriginalLocale()
    {
        return $this->originalLocale;
    }

    public function setOriginalLocale($originalLocale)
    {
        $this->originalLocale = $originalLocale;
    }
}
