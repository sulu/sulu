<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Application\Refresh;

interface ReferenceRefresherInterface
{
    public static function getResourceKey(): string;

    /**
     * Refreshes the references.
     *
     * @param array{
     *      resourceId: string,
     *      resourceKey: string,
     *      locale: string,
     *      stage: string
     *  }|null $filter
     *
     * Returns a generator which yields the object of refreshed references.
     * The optional filter can be used to filter the objects which should be refreshed.
     */
    public function refresh(?array $filter = null): \Generator;
}
