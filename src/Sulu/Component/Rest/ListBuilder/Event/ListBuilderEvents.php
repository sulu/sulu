<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Event;

/**
 * Class which holds the available events for the listbuilder.
 */
final class ListBuilderEvents
{
    /**
     * The listbuilder.create event is emitted right before a list is build and allows to add conditions and fields.
     * The event listener receives a ListBuilderEvent instance.
     *
     * @var string
     */
    const LISTBUILDER_CREATE = 'sulu.listbuilder.create';

    private function __construct()
    {
    }
}
