<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template;

/**
 * Resolves template for node types.
 */
class TemplateResolver implements TemplateResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve($nodeType, $templateKey)
    {
        if (Structure::NODE_TYPE_EXTERNAL_LINK === $nodeType) {
            $templateKey = 'external-link';
        } elseif (Structure::NODE_TYPE_INTERNAL_LINK === $nodeType) {
            $templateKey = 'internal-link';
        }

        return $templateKey;
    }
}
