<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

class AllOfsMetadata extends SchemaMetadata
{
    /**
     * @param SchemaMetadata[] $allOfs
     */
    public function __construct(array $allOfs)
    {
        parent::__construct([], [], $allOfs);
    }
}
