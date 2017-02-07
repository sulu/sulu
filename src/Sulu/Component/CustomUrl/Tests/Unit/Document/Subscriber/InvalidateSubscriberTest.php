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

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\Subscriber\InvalidateSubscriber;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;

class InvalidateSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testHandlePersist()
    {
        $manager = $this->prophesize(CustomUrlManagerInterface::class);
        $subscriber = new InvalidateSubscriber($manager->reveal());

        $customUrl = $this->prophesize(CustomUrlDocument::class);

        $document = $this->prophesize(BasePageDocument::class);
        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document->reveal());

        $manager->findByPage($document->reveal())->willReturn([$customUrl->reveal()]);
        $manager->invalidate($customUrl->reveal())->shouldBeCalled();

        $subscriber->handlePersist($event->reveal());
    }

    public function testHandlePersistOtherDocuments()
    {
        $manager = $this->prophesize(CustomUrlManagerInterface::class);
        $subscriber = new InvalidateSubscriber($manager->reveal());

        $customUrl = $this->prophesize(CustomUrlDocument::class);

        $document = new \stdClass();
        $event = $this->prophesize(PersistEvent::class);
        $event->getDocument()->willReturn($document);

        $manager->findByPage($document)->shouldNotBeCalled();
        $manager->invalidate($customUrl->reveal())->shouldNotBeCalled();

        $subscriber->handlePersist($event->reveal());
    }
}
