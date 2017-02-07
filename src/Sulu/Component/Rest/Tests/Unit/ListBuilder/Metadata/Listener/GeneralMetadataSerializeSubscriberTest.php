<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Metadata\Listener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Prophecy\Argument;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata as GeneralPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Listener\GeneralMetadataSerializeSubscriber;
use Sulu\Component\Rest\ListBuilder\Metadata\PropertyMetadata;

class GeneralMetadataSerializeSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $subscriber = new GeneralMetadataSerializeSubscriber();
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
            [true, true, true],
        ];
    }

    /**
     * @dataProvider testPostSerializeProvider
     */
    public function testPostSerialize($hasMetadata, $hasGeneralMetadata = false, $hasFilterType = false)
    {
        $visitor = $this->prophesize(JsonSerializationVisitor::class);
        $descriptor = $this->prophesize(FieldDescriptorInterface::class);
        $descriptor->getType()->willReturn('test-type');

        if ($hasMetadata) {
            $metadata = $this->prophesize(PropertyMetadata::class);
            $metadata->has(GeneralPropertyMetadata::class)->willReturn($hasGeneralMetadata);

            if ($hasGeneralMetadata) {
                $generalMetadata = $this->prophesize(GeneralPropertyMetadata::class);
                $generalMetadata->getDisplay()->willReturn(GeneralPropertyMetadata::DISPLAY_YES);

                $metadata->get(GeneralPropertyMetadata::class)->willReturn($generalMetadata->reveal());
                $visitor->addData('display', GeneralPropertyMetadata::DISPLAY_YES)->shouldBeCalled();

                if ($hasFilterType) {
                    $generalMetadata->getFilterType()->willReturn('test-input');
                    $generalMetadata->getFilterTypeParameters()->willReturn([]);

                    $visitor->addData('filter-type', 'test-input')->shouldBeCalled();
                    $visitor->addData('filter-type-parameters', [])->shouldBeCalled();
                } else {
                    $generalMetadata->getFilterType()->willReturn(null);
                }
            }

            $descriptor->getMetadata()->willReturn($metadata->reveal());
        } else {
            $descriptor->getMetadata()->willReturn(null);
        }

        if (!$hasMetadata || !$hasGeneralMetadata) {
            $visitor->addData('filter-type', 'test-type')->shouldBeCalled();
            $visitor->addData('filter-type-parameters', [])->shouldBeCalled();
        }

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($descriptor->reveal());
        $event->getVisitor()->willReturn($visitor->reveal());

        $subscriber = new GeneralMetadataSerializeSubscriber();

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

        $subscriber = new GeneralMetadataSerializeSubscriber();

        $subscriber->onPostSerialize($event->reveal());
    }
}
