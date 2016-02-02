<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Component\Content\Query\ContentQueryBuilder;

class NavigationQueryBuilder extends ContentQueryBuilder
{
    /**
     * @var array
     */
    protected $properties = ['navContexts'];

    /**
     * @var string|null
     */
    private $context = null;

    /**
     * @var string|null
     */
    private $parent = null;

    /**
     * Returns custom select statement.
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        $where = [];
        if ($this->context !== null) {
            $where[] = sprintf("page.[i18n:%s-navContexts] = '%s'", $locale, $this->context);
        }
        if ($this->parent !== null) {
            $where[] = sprintf("ISDESCENDANTNODE(page, '%s')", $this->parent);
        } else {
            $where[] = sprintf("ISDESCENDANTNODE(page, '%s')", '/cmf/' . $webspaceKey . '/contents');
        }

        return implode(' AND ', $where);
    }

    /**
     * Returns custom where statement.
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOrder($webspaceKey, $locale)
    {
        return 'page.[sulu:order]';
    }

    public function init(array $options)
    {
        $this->context = (isset($options['context'])) ? $options['context'] : null;
        $this->parent = (isset($options['parent'])) ? $options['parent'] : null;
        $this->excerpt = (isset($options['excerpt'])) ? $options['excerpt'] : true;
    }
}
