<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Listener;

use Sulu\Bundle\ResourceBundle\Resource\FilterListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Event\ListBuilderCreateEvent;

/**
 * Listens for events emitted by the list builder.
 */
class ListBuilderListener
{
    /**
     * @var FilterListBuilderInterface
     */
    protected $filterListBuilder;

    /**
     * @param FilterListBuilderInterface $filterListBuilder
     */
    public function __construct(FilterListBuilderInterface $filterListBuilder)
    {
        $this->filterListBuilder = $filterListBuilder;
    }

    /**
     * Will be called when a listbuilder.create event is emitted.
     *
     * @param ListBuilderCreateEvent $event
     */
    public function onListBuilderCreate(ListBuilderCreateEvent $event)
    {
        $this->filterListBuilder->applyFilterToList($event->getListBuilder());
    }
}
