<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Query;

interface ContentQueryBuilderInterface
{
    /**
     * Build query
     * @param string $webspaceKey
     * @param string[] $locales
     * @return string
     */
    public function build($webspaceKey, $locales);

    /**
     * initialize query builder
     * @param array $options
     */
    public function init(array $options);
}
