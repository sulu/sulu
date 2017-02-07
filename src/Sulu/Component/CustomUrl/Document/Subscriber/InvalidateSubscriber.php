<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document\Subscriber;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidate custom-url http-cache.
 */
class InvalidateSubscriber implements EventSubscriberInterface
{
    /**
     * @var CustomUrlManagerInterface
     */
    private $manager;

    public function __construct(CustomUrlManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [Events::PERSIST => 'handlePersist'];
    }

    /**
     * Invalidate custom-urls for persisted pages.
     *
     * @param PersistEvent $event
     */
    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!$document instanceof BasePageDocument) {
            return;
        }

        foreach ($this->manager->findByPage($document) as $customUrlDocument) {
            $this->manager->invalidate($customUrlDocument);
        }
    }
}
