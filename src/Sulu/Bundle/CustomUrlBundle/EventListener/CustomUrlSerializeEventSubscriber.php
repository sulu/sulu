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
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
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

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    public function __construct(GeneratorInterface $generator, UserManagerInterface $userManager)
    {
        $this->generator = $generator;
        $this->userManager = $userManager;
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
     * Add information to serialized custom-url document.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $customUrl = $event->getObject();
        $visitor = $event->getVisitor();

        if (!$customUrl instanceof CustomUrlDocument) {
            return;
        }

        if ($customUrl->getTargetDocument() !== null) {
            $visitor->addData('targetTitle', $customUrl->getTargetDocument()->getTitle());
        }

        $visitor->addData(
            'customUrl',
            $this->generator->generate($customUrl->getBaseDomain(), $customUrl->getDomainParts())
        );

        $visitor->addData('creatorFullName', $this->userManager->getFullNameByUserId($customUrl->getCreator()));
        $visitor->addData('changerFullName', $this->userManager->getFullNameByUserId($customUrl->getChanger()));
    }
}
