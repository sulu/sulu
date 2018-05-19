<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
        $this->eventDispatcher->dispatch(Events::FIND, $event);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function create($alias)
    {
        $event = new Event\CreateEvent($alias);
        $this->eventDispatcher->dispatch(Events::CREATE, $event);

        return $event->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($document, $locale = null, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::PERSIST)->resolve($options);

        $event = new Event\PersistEvent($document, $locale, $options);
        $this->eventDispatcher->dispatch(Events::PERSIST, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($document)
    {
        $event = new Event\RemoveEvent($document);
        $this->eventDispatcher->dispatch(Events::REMOVE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function move($document, $destId)
    {
        $event = new Event\MoveEvent($document, $destId);
        $this->eventDispatcher->dispatch(Events::MOVE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($document, $destPath)
    {
        $event = new Event\CopyEvent($document, $destPath);
        $this->eventDispatcher->dispatch(Events::COPY, $event);

        return $event->getCopiedPath();
    }

    /**
     * {@inheritdoc}
     */
    public function reorder($document, $destId)
    {
        $event = new Event\ReorderEvent($document, $destId);
        $this->eventDispatcher->dispatch(Events::REORDER, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function publish($document, $locale, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::PUBLISH)->resolve($options);

        $event = new Event\PublishEvent($document, $locale, $options);
        $this->eventDispatcher->dispatch(Events::PUBLISH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function unpublish($document, $locale)
    {
        $event = new Event\UnpublishEvent($document, $locale);
        $this->eventDispatcher->dispatch(Events::UNPUBLISH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function removeDraft($document, $locale)
    {
        $event = new Event\RemoveDraftEvent($document, $locale);
        $this->eventDispatcher->dispatch(Events::REMOVE_DRAFT, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function restore($document, $locale, $version, array $options = [])
    {
        $options = $this->getOptionsResolver(Events::RESTORE)->resolve($options);

        $event = new Event\RestoreEvent($document, $locale, $version, $options);
        $this->eventDispatcher->dispatch(Events::RESTORE, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($document)
    {
        $event = new Event\RefreshEvent($document);
        $this->eventDispatcher->dispatch(Events::REFRESH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $event = new Event\FlushEvent();
        $this->eventDispatcher->dispatch(Events::FLUSH, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $event = new Event\ClearEvent();
        $this->eventDispatcher->dispatch(Events::CLEAR, $event);
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($query, $locale = null, array $options = [])
    {
        $event = new Event\QueryCreateEvent($query, $locale, $options);
        $this->eventDispatcher->dispatch(Events::QUERY_CREATE, $event);

        return $event->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder()
    {
        $event = new Event\QueryCreateBuilderEvent();
        $this->eventDispatcher->dispatch(Events::QUERY_CREATE_BUILDER, $event);

        return $event->getQueryBuilder();
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
        $this->eventDispatcher->dispatch(Events::CONFIGURE_OPTIONS, $event);

        $this->optionsResolvers[$eventName] = $resolver;

        return $resolver;
    }
}
