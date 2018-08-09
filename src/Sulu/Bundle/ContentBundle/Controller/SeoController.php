<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use PHPCR\ItemNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Structure\SeoStructureExtension;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RouteResource("page-seo")
 */
class SeoController extends AbstractExtensionController
{
    protected function getExtensionName() {
        return SeoStructureExtension::SEO_EXTENSION_NAME;
    }
}
