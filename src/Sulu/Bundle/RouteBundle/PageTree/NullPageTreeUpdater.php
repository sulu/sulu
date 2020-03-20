<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\PageTree;

use Sulu\Bundle\PageBundle\Document\BasePageDocument;

/**
 * Does nothing and is only used a placeholder if the "page_route_cache" is "off".
 */
class NullPageTreeUpdater implements PageTreeUpdaterInterface
{
    public function update(BasePageDocument $document)
    {
        // do nothing
    }
}
