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
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Subscriber\WebspaceSubscriber;

class WebspaceSubscriberTest extends SubscriberTestCase
{
    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var WebspaceSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->subscriber = new WebspaceSubscriber($this->encoder->reveal(), $this->inspector->reveal());
    }

    public function testHandleWebspace()
    {
        $document = $this->prophesize(WebspaceBehavior::class);
        $this->persistEvent->getDocument()->willReturn($document);

        $this->inspector->getWebspace($document->reveal())->willReturn('example');
        $this->accessor->set('webspaceName', 'example')->shouldBeCalled();

        $this->subscriber->handleWebspace($this->persistEvent->reveal());
    }
}
