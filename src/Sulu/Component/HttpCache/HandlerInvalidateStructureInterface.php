<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

use Sulu\Component\Content\Compat\StructureInterface;

/**
 * Handlers implementing will invalidate the given structure with the
 * caching proxy server.
 *
 * Note that this is called during the request therefore the task of this
 * interfaces method should normally be to record references to the structures
 * which should later be invalidated in the `flush` interface.
 */
interface HandlerInvalidateStructureInterface extends HandlerInterface
{
    /**
     * Invalidate the given structure.
     *
     * @param StructureInterface
     */
    public function invalidateStructure(StructureInterface $structure);
}
