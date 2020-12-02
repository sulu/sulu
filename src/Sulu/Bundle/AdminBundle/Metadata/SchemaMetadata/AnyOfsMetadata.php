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

class AnyOfsMetadata extends SchemaMetadata
{
    /**
     * @param SchemaMetadata[] $anyOfs
     */
    public function __construct(array $anyOfs)
    {
        parent::__construct([], $anyOfs);
    }
}
