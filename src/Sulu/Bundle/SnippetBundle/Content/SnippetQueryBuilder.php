<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Content;

use Sulu\Component\Content\SmartContent\QueryBuilder;

/**
 * QueryBuilder for snippets.
 */
class SnippetQueryBuilder extends QueryBuilder
{
    protected static $mixinTypes = ['sulu:snippet'];

    public function buildWhere($webspaceKey, $locale)
    {
        $sqlWhere = parent::buildWhere($webspaceKey, $locale);
        if ($types = $this->getConfig('types')) {
            $sqlWhere .= ' AND (' . implode(' OR ', array_map(function($type) {
                return 'page.[template] = "' . $type . '"';
            }, $types)) . ')';
        }

        return $sqlWhere;
    }
}
