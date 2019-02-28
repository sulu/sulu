<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use Sulu\Bundle\PageBundle\Content\Structure\ExcerptStructureExtension;

/**
 * @RouteResource("page-excerpt")
 */
class ExcerptController extends AbstractExtensionController
{
    protected function getExtensionName()
    {
        return ExcerptStructureExtension::EXCERPT_EXTENSION_NAME;
    }
}
