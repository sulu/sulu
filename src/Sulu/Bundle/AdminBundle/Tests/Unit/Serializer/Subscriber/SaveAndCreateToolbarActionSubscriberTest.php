<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\SaveAndCreateToolbarAction;
use Sulu\Bundle\AdminBundle\Serializer\Subscriber\SaveAndCreateToolbarActionSubscriber;
use Symfony\Contracts\Translation\TranslatorInterface;

class SaveAndCreateToolbarActionSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var SaveAndCreateToolbarActionSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->subscriber = new SaveAndCreateToolbarActionSubscriber($this->translator->reveal());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                [
                    'event' => Events::POST_SERIALIZE,
                    'format' => 'json',
                    'method' => 'onPostSerialize',
                    'class' => SaveAndCreateToolbarAction::class,
                ],
            ],
            SaveAndCreateToolbarActionSubscriber::getSubscribedEvents()
        );
    }

    public function testOnPostSerializeTranslatesLabelAndTitle(): void
    {
        $toolbarAction = new SaveAndCreateToolbarAction(
            'sulu_admin.save',
            'sulu_admin.cancel',
            'form',
            'tasks',
            'entityId',
            null,
            ['entityClass' => 'Entity'],
            null,
            'isDraft'
        );

        $this->translator->trans('sulu_admin.save', [], 'admin')->willReturn('Label');
        $this->translator->trans('sulu_admin.cancel', [], 'admin')->willReturn('Title');

        $navigator = $this->prophesize(GraphNavigatorInterface::class);
        $navigator->accept(Argument::any(), Argument::any())->will(fn (array $arguments) => $arguments[0]);
        $visitor = new JsonSerializationVisitor();
        $visitor->setNavigator($navigator->reveal());
        $metadata = new ClassMetadata(SaveAndCreateToolbarAction::class);
        $type = ['name' => SaveAndCreateToolbarAction::class, 'params' => []];
        $visitor->startVisitingObject($metadata, $toolbarAction, $type);

        $event = $this->prophesize(ObjectEvent::class);
        $event->getObject()->willReturn($toolbarAction);
        $event->getVisitor()->willReturn($visitor);

        $this->subscriber->onPostSerialize($event->reveal());

        $this->assertSame(
            [
                'options' => [
                    'label' => 'Label',
                    'title' => 'Title',
                    'formKey' => 'form',
                    'resourceKey' => 'tasks',
                    'idParameter' => 'entityId',
                    'parameters' => ['entityClass' => 'Entity'],
                    'visible_condition' => 'isDraft',
                ],
            ],
            $visitor->endVisitingObject($metadata, $toolbarAction, $type)
        );
    }
}
