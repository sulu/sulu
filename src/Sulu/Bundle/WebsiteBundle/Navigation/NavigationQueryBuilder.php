<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Bundle\WebsiteBundle\ContentQuery\ContentQueryBuilder;

class NavigationQueryBuilder extends ContentQueryBuilder
{
    protected $properties = array('navContexts');

    private $depth = 1;
    private $context = '';

    /**
     * Returns custom select statement
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        $where = array();
        if ($this->depth !== -1) {
            $where[] = $this->buildDepthWhere();
        }

        return implode(', ', $where);
    }

    private function buildDepthWhere()
    {
        $path = '';
        for ($i = 0; $i < $this->depth; $i++) {
            $path .= '/%';
        }

        return "page.[jcr:path] LIKE '" . $path . "' AND NOT page.[jcr:path] LIKE '" . $path . "/%'";
    }

    /**
     * Returns custom where statement
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        return '';
    }

    public function init(array $options)
    {
        $this->depth = (isset($options['depth'])) ? $options['depth'] : 1;
    }
}
