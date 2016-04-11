<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebspaceSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    ) {
        $this->encoder = $encoder;
        $this->inspector = $inspector;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['handleWebspace'],
            // should happen after content is hydrated
            Events::HYDRATE => ['handleWebspace', -10],
        ];
    }

    /**
     * @param AbstractMappingEvent $event
     */
    public function handleWebspace(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof WebspaceBehavior) {
            return;
        }

        $webspaceName = $this->inspector->getWebspace($document);
        $event->getAccessor()->set('webspaceName', $webspaceName);
    }
}
