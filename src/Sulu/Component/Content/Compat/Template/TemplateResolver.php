<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Template;

use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Document\RedirectType;

/**
 * TODO: Internal / external links should not be structure types
 */
class TemplateResolver
{
    /**
     * {@inheritdoc}
     */
    public function resolve($nodeType, $templateKey)
    {
        if ($nodeType === RedirectType::EXTERNAL) {
            $templateKey = 'external-link';
        } elseif ($nodeType === RedirectType::INTERNAL) {
            $templateKey = 'internal-link';
        }

        return $templateKey;
    }
}
