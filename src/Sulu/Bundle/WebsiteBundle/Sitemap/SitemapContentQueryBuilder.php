<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use Sulu\Bundle\WebsiteBundle\ContentQuery\ContentQueryBuilder;

/**
 * Creates query for a minimum content pages (title and url)
 */
class SitemapContentQueryBuilder extends ContentQueryBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options)
    {
    }
}
