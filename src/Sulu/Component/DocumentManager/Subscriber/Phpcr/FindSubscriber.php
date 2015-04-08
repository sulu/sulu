<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Event\FindEvent;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * This class is responsible for finding documents
 */
class FindSubscriber implements EventSubscriberInterface
{
    private $metadataFactory;
    private $eventDispatcher;
    private $nodeManager;

    /**
     * @param MetadataFactory $metadataFactory
     */
    public function __construct(
        MetadataFactory $metadataFactory,
        NodeManager $nodeManager,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->metadataFactory = $metadataFactory;
        $this->nodeManager = $nodeManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::FIND => array('handleFind', 500),
        );
    }

    public function handleFind(FindEvent $event)
    {
        $aliasOrClass = $event->getAliasOrClass();

        $node = $this->nodeManager->find($event->getId());
        $hydrateEvent = new HydrateEvent($node, $event->getLocale(), $event->getAliasOrClass());
        $this->eventDispatcher->dispatch(Events::HYDRATE, $hydrateEvent);
        $document = $hydrateEvent->getDocument();

        if ($aliasOrClass) {
            $this->checkAliasOrClass($aliasOrClass, $document);
        }

        $event->setDocument($hydrateEvent->getDocument());
    }

    private function checkAliasOrClass($aliasOrClass, $document)
    {
        if ($this->metadataFactory->hasAlias($aliasOrClass)) {
            $class = $this->metadataFactory->getMetadataForAlias($aliasOrClass)->getClass();
        } elseif (!class_exists($aliasOrClass)) {
            throw new DocumentManagerException(sprintf(
                'Unknown class specified and no alias exists for "%s"',
                $aliasOrClass
            ));
        } else {
            $class = $aliasOrClass;
        }

        if (get_class($document) !== $class) {
            throw new DocumentNotFoundException(sprintf(
                'Requested document of type "%s" but got document of type "%s"',
                $aliasOrClass,
                get_class($document)
            ));
        }
    }
}
