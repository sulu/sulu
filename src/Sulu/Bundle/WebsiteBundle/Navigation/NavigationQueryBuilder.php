<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Bundle\PageBundle\Content\Types\SegmentSelect;
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
     * @var string|null
     */
    private $segmentKey = null;

    /**
     * Returns custom select statement.
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        $where = [];
        if (null !== $this->context) {
            $where[] = \sprintf("page.[i18n:%s-navContexts] = '%s'", $locale, $this->context);
        }

        if (null !== $this->parent) {
            $where[] = \sprintf("ISDESCENDANTNODE(page, '%s')", $this->parent);
        } else {
            $where[] = \sprintf("ISDESCENDANTNODE(page, '%s')", '/cmf/' . $webspaceKey . '/contents');
        }

        if (null !== $this->segmentKey) {
            $where[] = \sprintf(
                "(page.[i18n:%s-excerpt-segments%s%s] = '%s' OR page.[i18n:%s-excerpt-segments%s%s] IS NULL)",
                $locale,
                SegmentSelect::SEPARATOR,
                $webspaceKey,
                $this->segmentKey,
                $locale,
                SegmentSelect::SEPARATOR,
                $webspaceKey
            );
        }

        return \implode(' AND ', $where);
    }

    /**
     * Returns custom where statement.
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        return '';
    }

    protected function buildOrder($webspaceKey, $locale)
    {
        return 'page.[sulu:order]';
    }

    public function init(array $options)
    {
        $this->context = (isset($options['context'])) ? $options['context'] : null;
        $this->parent = (isset($options['parent'])) ? $options['parent'] : null;
        $this->excerpt = (isset($options['excerpt'])) ? $options['excerpt'] : true;
        $this->segmentKey = (isset($options['segmentKey'])) ? $options['segmentKey'] : null;
    }
}
