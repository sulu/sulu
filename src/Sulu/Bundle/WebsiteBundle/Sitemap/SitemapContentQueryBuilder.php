<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use Sulu\Component\Content\Query\ContentQueryBuilder;

/**
 * Creates query for a minimum content pages (title and url).
 */
class SitemapContentQueryBuilder extends ContentQueryBuilder
{
    protected function buildWhere($webspaceKey, $locale)
    {
        return \sprintf("
        (
            (
                ISDESCENDANTNODE('/cmf/%s/contents')
                OR ISSAMENODE('/cmf/%s/contents')
            ) AND (
                page.[i18n:%s-seo-hideInSitemap] IS NULL
                OR page.[i18n:%s-seo-hideInSitemap] = false
            )
        )", $webspaceKey, $webspaceKey, $locale, $locale);
    }

    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        return '';
    }

    public function init(array $options)
    {
    }
}
