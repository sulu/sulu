<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;

class WebspaceSubscriber extends AbstractMappingSubscriber
{
    private $inspector;

    public function __construct(
        PropertyEncoder $encoder,
        DocumentInspector $inspector
    ) {
        parent::__construct($encoder);
        $this->inspector = $inspector;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            // should happen after content is hydrated
            Events::HYDRATE => array('handleHydrate', -10),
            Events::PERSIST => array('handlePersist', 10),
        );
    }

    public function supports($document)
    {
        return $document instanceof WebspaceBehavior;
    }

    /**
     * @param HydrateEvent $event
     */
    public function doHydrate(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();
        $webspace = $this->inspector->getWebspace($document);
        $event->getAccessor()->set('webspaceName', $webspace);
    }

    /**
     * @param PersistEvent $event
     */
    public function doPersist(PersistEvent $event)
    {
        $this->doHydrate($event);
    }
}
