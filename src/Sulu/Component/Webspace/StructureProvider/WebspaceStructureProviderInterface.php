<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\StructureProvider;

use Sulu\Component\Content\Structure\Page;

/**
 * Provide templates which are implemented in a single webspace.
 */
interface WebspaceStructureProviderInterface
{
    /**
     * Returns page templates which are implemented in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return Page[]
     */
    public function getStructures($webspaceKey);
}
