<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Bundle\AdminBundle\Admin\View\SaveAndCreateToolbarAction;
use Symfony\Contracts\Translation\TranslatorInterface;

class SaveAndCreateToolbarActionSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array<array{event: string, format: string, method: string, class: class-string}>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
                'class' => SaveAndCreateToolbarAction::class,
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $saveAndCreateToolbarAction = $event->getObject();
        $visitor = $event->getVisitor();

        if (!$saveAndCreateToolbarAction instanceof SaveAndCreateToolbarAction
            || !$visitor instanceof JsonSerializationVisitor
        ) {
            return;
        }

        $options = $saveAndCreateToolbarAction->getOptions();
        foreach (['label', 'title'] as $key) {
            $value = $options[$key] ?? null;
            if (\is_string($value)) {
                $options[$key] = $this->translator->trans($value, [], 'admin');
            }
        }

        $serializedOptions = $visitor->visitArray($options, ['name' => 'array', 'params' => []]);
        $visitor->setData('options', $serializedOptions);
    }
}
