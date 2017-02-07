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

use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An object of this class is emitted along with the listbuilder.create event.
 */
class ListBuilderCreateEvent extends Event
{
    /**
     * @var ListbuilderInterface
     */
    protected $listBuilder;

    public function __construct(ListbuilderInterface $listBuilder)
    {
        $this->listBuilder = $listBuilder;
    }

    /**
     * @return ListBuilderInterface
     */
    public function getListBuilder()
    {
        return $this->listBuilder;
    }
}
