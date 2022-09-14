<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

interface CacheLifetimeBehaviourInterface
{
    /**
     * cacheLifeTime of template definition.
     *
     * @return array{
     *     type: string,
     *     value: string,
     * }
     */
    public function getCacheLifeTime();
}
