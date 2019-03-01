<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

/**
 * Handlers implementing will invalidate the given reference with the
 * caching proxy server.
 *
 * Note that this is called during the request therefore the task of this
 * interfaces method should normally be to record references to the structures
 * which should later be invalidated in the `flush` interface.
 */
interface HandlerInvalidateReferenceInterface extends HandlerInvalidateStructureInterface
{
    /**
     * Invalidate the given reference.
     *
     * @param string $alias
     * @param string $id
     */
    public function invalidateReference($alias, $id);
}
