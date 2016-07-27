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

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ShadowLocaleSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var ShadowLocaleSubscriber
     */
    private $shadowLocaleSubscriber;

    public function setUp()
    {
        $this->propertyEncoder = $this->prophesize(PropertyEncoder::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);

        $this->shadowLocaleSubscriber = new ShadowLocaleSubscriber(
            $this->propertyEncoder->reveal(),
            $this->documentInspector->reveal(),
            $this->documentRegistry->reveal()
        );
    }

    public function testHandlePersistEmptyLocale()
    {
        $document = $this->prophesize(ShadowLocaleBehavior::class);
        $document->isShadowLocaleEnabled()->shouldNotBeCalled();

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn(null);
        $event->getNode()->shouldNotBeCalled();

        $this->shadowLocaleSubscriber->saveShadowProperties($event->reveal());
    }

    public function testHandlePersistForNonConcreteLocale()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            'Attempting to create shadow for "de" on a non-concrete locale "de_at" '
            . 'for document at "/cmf/sulu_io/contents/test". Concrete languages are "de", "en"'
        );

        $document = $this->prophesize(ShadowLocaleBehavior::class)
            ->willImplement(LocaleBehavior::class);
        $document->isShadowLocaleEnabled()->willReturn(true);
        $document->getLocale()->willReturn('de');
        $document->getShadowLocale()->willReturn('de_at');

        $node = $this->prophesize(NodeInterface::class);
        $node->revert()->shouldBeCalled();

        $this->documentInspector->getConcreteLocales($document)->willReturn(['de', 'en']);
        $this->documentInspector->getNode($document)->willReturn($node->reveal());
        $this->documentInspector->getPath($document)->willReturn('/cmf/sulu_io/contents/test');

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn('de');

        $this->shadowLocaleSubscriber->saveShadowProperties($event->reveal());
    }

    public function testHandlePersistForSameLocale()
    {
        $this->setExpectedException(\RuntimeException::class, 'Document cannot be a shadow of itself for locale "de"');

        $document = $this->prophesize(ShadowLocaleBehavior::class)
            ->willImplement(LocaleBehavior::class);
        $document->isShadowLocaleEnabled()->willReturn(true);
        $document->getLocale()->willReturn('de');
        $document->getShadowLocale()->willReturn('de');

        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn('de');

        $this->shadowLocaleSubscriber->saveShadowProperties($event->reveal());
    }

    public function testHandleHydrate()
    {
        $document = $this->prophesize(ShadowLocaleBehavior::class);

        $event = $this->prophesize(HydrateEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getOption('load_shadow_content')->willReturn(false);

        $event->getNode()->shouldNotBeCalled();
        $this->shadowLocaleSubscriber->handleHydrate($event->reveal());
    }
}
