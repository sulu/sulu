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

use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\CustomUrl\Generator\GeneratorInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles document-manager events for custom-urls.
 */
class CustomUrlSubscriber implements EventSubscriberInterface
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
        return [Events::PERSIST => 'handlePersist'];
    }

    public function handlePersist(PersistEvent $event)
    {
        $document = $event->getDocument();
        if (!($document instanceof CustomUrlBehavior)) {
            return;
        }

        // TODO localization if multilingual is true

        $domains = $this->generator->generate($document->getBaseDomain(), $document->getDomainParts());

    }
}
