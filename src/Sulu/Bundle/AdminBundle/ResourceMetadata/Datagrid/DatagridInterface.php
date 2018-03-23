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

use Sulu\Bundle\AdminBundle\ResourceMetadata\ResourceMetadataInterface;

interface DatagridInterface extends ResourceMetadataInterface
{
    public function getDatagrid(): Datagrid;
}
