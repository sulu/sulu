<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing\Enhancers;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redirects to an internal page.
 */
class InternalLinkEnhancer implements RouteEnhancerInterface
{
    /**
     * {@inheritdoc}
     */
    public function enhance(array $defaults, Request $request)
    {
        if (!array_key_exists('_structure', $defaults)
            || Structure::NODE_TYPE_INTERNAL_LINK !== $defaults['_structure']->getNodeType()
        ) {
            return $defaults;
        }

        /** @var PageBridge $structure */
        $structure = $defaults['_structure'];

        return array_merge(
            $defaults,
            [
                '_structure' => $structure->getInternalLinkContent(),
            ]
        );
    }
}
