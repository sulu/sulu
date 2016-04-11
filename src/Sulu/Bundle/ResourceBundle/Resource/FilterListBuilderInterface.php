<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource;

use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

/**
 * Component which triggers the generation of additional statements from the conditions of a filter
 * and applies them to the list builder.
 */
interface FilterListBuilderInterface
{
    /**
     * Applies the conditions from a filter to the listbuilder.
     *
     * @param ListbuilderInterface $listBuilder
     *
     * @return mixed
     */
    public function applyFilterToList(ListBuilderInterface $listBuilder);
}
