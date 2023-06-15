<?php

namespace Sulu\Bundle\ReferenceBundle\Application\Refresh;

interface ReferenceRefresherInterface
{
    public static function getResourceKey(): string;

    /**
     * Refreshes the references.
     *
     * Returns a generator which yields the object of refreshed references.
     */
    public function refresh(): \Generator;

}
