<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\ResourceMetadata\Datagrid;

interface DatagridInterface
{
    // FIXME this method should always return a datagrid and never null, see https://github.com/sulu/sulu/issues/3907
    public function getDatagrid(): ?Datagrid;
}
