<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\EventListener;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;

/**
 * Event subscriber that adds custom-url and target-title to list response.
 */
class CustomUrlSerializeEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var GeneratorInterface
     */
    private $generator;

    public function __construct(GeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'format' => 'json',
                'method' => 'onPostSerialize',
            ],
        ];
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $customUrl = $event->getObject();
        $visitor = $event->getVisitor();

        if (!$customUrl instanceof CustomUrlDocument) {
            return;
        }

        $visitor->addData('targetTitle', $customUrl->getTarget()->getTitle());
        $visitor->addData(
            'customUrl',
            $this->generator->generate($customUrl->getBaseDomain(), $customUrl->getDomainParts())
        );
    }
}
