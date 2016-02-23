<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\DependencyInjection\Compiler;

use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\SubscriberPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SubscriberPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventSubscriberInterface
     */
    private $subscriber1;

    /**
     * @var EventSubscriberInterface
     */
    private $subscriber2;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher1;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher2;

    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var SubscriberPass
     */
    private $pass;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->subscriber1 = $this->prophesize(EventSubscriberInterface::class);
        $this->subscriber2 = $this->prophesize(EventSubscriberInterface::class);

        $this->dispatcher1 = $this->container->register('sulu_document_manager.event_dispatcher.default', EventDispatcherInterface::class);
        $this->dispatcher2 = $this->container->register('sulu_document_manager.event_dispatcher.prod', EventDispatcherInterface::class);
        $this->pass = new SubscriberPass();
        $this->container->setParameter('sulu_document_manager.managers', ['default', 'prod']);
    }

    /**
     * It should throw an exception if the subscriber is not public.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The service "subscriber_1" must be public as event subscribers are lazy-loaded
     */
    public function testDispatcherNotPublic()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', get_class($this->subscriber1->reveal()));
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber', ['manager' => 'default']);
        $subscriberDef1->setPublic(false);
        $this->pass->process($this->container);
    }

    /**
     * It should throw an exception if the subscriber is abstract.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The service "subscriber_1" must not be abstract as event subscribers are lazy-loaded.
     */
    public function testDispatcherAbstract()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', get_class($this->subscriber1->reveal()));
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber', ['manager' => 'default']);
        $subscriberDef1->setAbstract(true);
        $this->pass->process($this->container);
    }

    /**
     * It should throw an exception if the subscriber does not implement the event subscriber interface.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Service "subscriber_1" must implement interface "Symfony\Component\EventDispatcher\EventSubscriberInterface"
     */
    public function testDispatcherNotImplementing()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', 'stdClass');
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber', ['manager' => 'default']);
        $this->pass->process($this->container);
    }

    /**
     * It should throw an exception if the event subscriber specifies a non-existant manager.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown document manager "not-existing" specified for event subscriber "subscriber_1". Known document managers: "default", "prod"
     */
    public function testNonExistingManager()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', get_class($this->subscriber1->reveal()));
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber', ['manager' => 'not-existing']);
        $this->pass->process($this->container);
    }

    /**
     * It should throw an exception if the tag has invalid keys.
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Subscriber "subscriber_1" has invalid tag keys: "bar", valid keys: "manager"
     */
    public function testInvalidKeys()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', get_class($this->subscriber1->reveal()));
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber', ['bar' => 'foo']);
        $this->pass->process($this->container);
    }

    /**
     * It should add subscribers to specific event dispatchers.
     */
    public function testSpecificDispatchers()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', get_class($this->subscriber1->reveal()));
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber', ['manager' => 'default']);

        $subscriberDef2 = $this->container->register('subscriber_2', get_class($this->subscriber2->reveal()));
        $subscriberDef2->addTag('sulu_document_manager.event_subscriber', ['manager' => 'prod']);

        $this->pass->process($this->container);

        $calls = $this->dispatcher1->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertEquals($calls[0], [
            'addSubscriberService',
            [
                'subscriber_1',
                get_class($this->subscriber1->reveal()),
            ],
        ]);

        $calls = $this->dispatcher2->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertEquals($calls[0], [
            'addSubscriberService',
            [
                'subscriber_2',
                get_class($this->subscriber2->reveal()),
            ],
        ]);
    }

    /**
     * It should allow a comma-separated list of managers.
     */
    public function testSpecificDispatchersCommaSeparated()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', get_class($this->subscriber1->reveal()));
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber', ['manager' => 'default,prod']);

        $this->pass->process($this->container);

        $calls = $this->dispatcher1->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertEquals($calls[0], [
            'addSubscriberService',
            [
                'subscriber_1',
                get_class($this->subscriber1->reveal()),
            ],
        ]);

        $calls = $this->dispatcher2->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertEquals($calls[0], [
            'addSubscriberService',
            [
                'subscriber_1',
                get_class($this->subscriber1->reveal()),
            ],
        ]);
    }

    /**
     * It should add to all managers if no manager is specified.
     */
    public function testAllManagers()
    {
        $subscriberDef1 = $this->container->register('subscriber_1', get_class($this->subscriber1->reveal()));
        $subscriberDef1->addTag('sulu_document_manager.event_subscriber');

        $subscriberDef2 = $this->container->register('subscriber_2', get_class($this->subscriber2->reveal()));
        $subscriberDef2->addTag('sulu_document_manager.event_subscriber');

        $this->pass->process($this->container);

        $calls = $this->dispatcher1->getMethodCalls();

        $this->assertCount(2, $calls);
        $this->assertEquals($calls[0], [
            'addSubscriberService',
            [
                'subscriber_1',
                get_class($this->subscriber1->reveal()),
            ],
        ]);
        $this->assertEquals($calls[1], [
            'addSubscriberService',
            [
                'subscriber_2',
                get_class($this->subscriber2->reveal()),
            ],
        ]);

        $calls = $this->dispatcher2->getMethodCalls();

        $this->assertCount(2, $calls);
        $this->assertEquals($calls[0], [
            'addSubscriberService',
            [
                'subscriber_1',
                get_class($this->subscriber1->reveal()),
            ],
        ]);
    }
}
