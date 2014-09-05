<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ContentQuery;

/**
 * Interface for content query
 */
interface ContentQueryInterface
{
    /**
     * Executes a query and returns loaded content as array
     * @param string $webspaceKey
     * @param string[] $locales
     * @param ContentQueryBuilderInterface $contentQueryBuilder
     * @param bool $flat
     * @return array
     */
    public function execute($webspaceKey, $locales, ContentQueryBuilderInterface $contentQueryBuilder, $flat = true);
} 
