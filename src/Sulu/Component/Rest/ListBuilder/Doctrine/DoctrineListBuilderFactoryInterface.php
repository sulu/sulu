<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

/**
 * Defines the interface for the Factory of the DoctrineListBuilde.
 */
interface DoctrineListBuilderFactoryInterface
{
    /**
     * @param string $entityName
     *
     * @return ListBuilderInterface
     */
    public function create($entityName);
}
