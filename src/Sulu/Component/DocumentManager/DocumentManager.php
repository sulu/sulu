<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentManager implements DocumentManagerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array Cached options resolver instances
     */
    private $optionsResolvers = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function find($identifier, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::FIND)->resolve($options);

        $event = new Event\FindEvent($identifier, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::FIND);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function create($alias)
    {
        $event = new Event\CreateEvent($alias);
        $this->eventDispatcher->dispatch($event, Events::CREATE);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($document, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::PERSIST)->resolve($options);

        $event = new Event\PersistEvent($document, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::PERSIST);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $event = new Event\RemoveEvent($document);
        $this->eventDispatcher->dispatch($event, Events::REMOVE);
    }

    /**
     * {@inheritdoc}
     */
    public function move($document, $destId)
    {
        $event = new Event\MoveEvent($document, $destId);
        $this->eventDispatcher->dispatch($event, Events::MOVE);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($document, $destPath)
    {
        $event = new Event\CopyEvent($document, $destPath);
        $this->eventDispatcher->dispatch($event, Events::COPY);

        return $event->getCopiedPath();
    }

    /**
     * {@inheritdoc}
     */
    public function reorder($document, $destId)
    {
        $event = new Event\ReorderEvent($document, $destId);
        $this->eventDispatcher->dispatch($event, Events::REORDER);
    }

    /**
     * {@inheritdoc}
     */
    public function publish($document, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::PUBLISH)->resolve($options);

        $event = new Event\PublishEvent($document, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::PUBLISH);
    }

    /**
     * {@inheritdoc}
     */
    public function unpublish($document, $locale)
    {
        $event = new Event\UnpublishEvent($document, $locale);
        $this->eventDispatcher->dispatch($event, Events::UNPUBLISH);
    }

    /**
     * {@inheritdoc}
     */
    public function removeDraft($document, $locale)
    {
        $event = new Event\RemoveDraftEvent($document, $locale);
        $this->eventDispatcher->dispatch($event, Events::REMOVE_DRAFT);
    }

    /**
     * {@inheritdoc}
     */
    public function restore($document, $locale, $version, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::RESTORE)->resolve($options);

        $event = new Event\RestoreEvent($document, $locale, $version, $options);
        $this->eventDispatcher->dispatch($event, Events::RESTORE);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($document)
    {
        $event = new Event\RefreshEvent($document);
        $this->eventDispatcher->dispatch($event, Events::REFRESH);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $event = new Event\FlushEvent();
        $this->eventDispatcher->dispatch($event, Events::FLUSH);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $event = new Event\ClearEvent();
        $this->eventDispatcher->dispatch($event, Events::CLEAR);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($query, $locale = null, array $options = [])
    {
        $event = new Event\QueryCreateEvent($query, $locale, $options);
        $this->eventDispatcher->dispatch($event, Events::QUERY_CREATE);

        return $event->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    private function getOptionsResolver($eventName)
    {
        if (isset($this->optionsResolvers[$eventName])) {
            return $this->optionsResolvers[$eventName];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefault('locale', null);

        $event = new Event\ConfigureOptionsEvent($resolver);
        $this->eventDispatcher->dispatch($event, Events::CONFIGURE_OPTIONS);

        $this->optionsResolvers[$eventName] = $resolver;

        return $resolver;
    }
}
