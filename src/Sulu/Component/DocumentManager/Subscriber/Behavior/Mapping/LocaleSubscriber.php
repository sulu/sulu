<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maps the locale.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentRegistry
     */
    private $registry;

    public function __construct(DocumentRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::HYDRATE => ['handleLocale', 410],
            Events::PERSIST => ['handleLocale', 410],
        ];
    }

    /**
     * @param AbstractMappingEvent $event
     *
     * @throws DocumentManagerException
     */
    public function handleLocale(AbstractMappingEvent $event)
    {
        $document = $event->getDocument();

        if (!$document instanceof LocaleBehavior) {
            return;
        }

        $locale = $this->registry->getLocaleForDocument($document);
        $document->setLocale($locale);
        $document->setOriginalLocale($locale);
    }
}
