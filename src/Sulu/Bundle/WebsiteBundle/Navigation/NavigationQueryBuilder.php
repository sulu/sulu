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
    /**
     * @var array
     */
    protected $properties = array('navContexts');

    /**
     * @var string|null
     */
    private $context = null;

    /**
     * @var string|null
     */
    private $parent = null;

    /**
     * Returns custom select statement
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        $where = array();
        if ($this->context !== null) {
            $where[] = sprintf("page.[i18n:%s-navContexts] = '%s'", $locale, $this->context);
        }
        if ($this->parent !== null) {
            $where[] = sprintf("ISDESCENDANTNODE(page, '%s')", $this->parent);
        }

        return implode(' AND ', $where);
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
        $this->context = (isset($options['context'])) ? $options['context'] : null;
        $this->parent = (isset($options['parent'])) ? $options['parent'] : null;
    }
}
