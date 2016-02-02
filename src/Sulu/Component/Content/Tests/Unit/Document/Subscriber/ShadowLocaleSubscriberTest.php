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
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class ShadowLocaleSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testHandlePersistEmptyLocale()
    {
        $encoder = $this->prophesize(PropertyEncoder::class);
        $inspector = $this->prophesize(DocumentInspector::class);
        $registry = $this->prophesize(DocumentRegistry::class);

        $document = $this->prophesize(ShadowLocaleBehavior::class);
        $document->isShadowLocaleEnabled()->shouldNotBeCalled();
        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document->reveal());
        $event->getLocale()->willReturn(null);
        $event->getNode()->shouldNotBeCalled();

        $subscriber = new ShadowLocaleSubscriber($encoder->reveal(), $inspector->reveal(), $registry->reveal());

        $subscriber->handlePersist($event->reveal());
    }
}
