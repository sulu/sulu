<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Tests\Unit\Resource\Metadata\Listener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Prophecy\Argument;
use Sulu\Bundle\ResourceBundle\Resource\Metadata\Listener\FilterMetadataSerializeSubscriber;
use Sulu\Bundle\ResourceBundle\Resource\Metadata\PropertyMetadata as FilterPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\PropertyMetadata;

class FilterMetadataSerializeSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $subscriber = new FilterMetadataSerializeSubscriber();
        $events = $subscriber->getSubscribedEvents();

        $refl = new \ReflectionClass($subscriber);

        foreach ($events as $event) {
            $this->assertTrue($refl->hasMethod($event['method']));
            $this->assertContains(
                $event['event'],
                [Events::POST_DESERIALIZE, Events::POST_SERIALIZE, Events::PRE_DESERIALIZE, Events::PRE_SERIALIZE]
            );
            $this->assertEquals('json', $event['format']);
        }
    }

    public function testPostSerializeProvider()
    {
        return [
            [false],
            [true, false],
            [true, true],
        ];
    }

    /**
     * @dataProvider testPostSerializeProvider
     */
    public function testPostSerialize($hasMetadata, $hasFilterMetadata = false)
    {
        $visitor = $this->prophesize(JsonSerializationVisitor::class);
        $descriptor = $this->prophesize(FieldDescriptorInterface::class);

        if ($hasMetadata) {
            $metadata = $this->prophesize(PropertyMetadata::class);
            $metadata->has(FilterPropertyMetadata::class)->willReturn($hasFilterMetadata);

            if ($hasFilterMetadata) {
                $filterMetadata = $this->prophesize(FilterPropertyMetadata::class);
                $filterMetadata->getInputType()->willReturn('test-input');
                $filterMetadata->getParameters()->willReturn([]);

                $metadata->get(FilterPropertyMetadata::class)->willReturn($filterMetadata->reveal());

                $visitor->addData('filter:input-type', 'test-input')->shouldBeCalled();
                $visitor->addData('filter:parameters', [])->shouldBeCalled();
            }

            $descriptor->getMetadata()->willReturn($metadata->reveal());
        } else {
            $descriptor->getMetadata()->willReturn(null);
            $descriptor->getType()->willReturn('test-type');
            $visitor->addData('filter:input-type', 'test-type')->shouldBeCalled();
        }

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($descriptor->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber = new FilterMetadataSerializeSubscriber();

        $subscriber->onPostSerialize($event->reveal());
    }

    public function testPostSerializeWrongObject()
    {
        $visitor = $this->prophesize(JsonSerializationVisitor::class);
        $object = $this->prophesize(\stdClass::class);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($object->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $visitor->addData(Argument::any(), Argument::any())->shouldNotBeCalled();

        $subscriber = new FilterMetadataSerializeSubscriber();

        $subscriber->onPostSerialize($event->reveal());
    }
}
