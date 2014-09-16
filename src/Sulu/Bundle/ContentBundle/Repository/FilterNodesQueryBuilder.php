<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Repository;

use Sulu\Component\Content\StructureInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * helper class to build a filter node query
 */
class FilterNodesQueryBuilder
{
    /**
     * config array
     * @var array
     */
    private $filterConfig;

    /**
     * parent uuid of filtered nodes
     * @var string
     */
    private $parent;

    /**
     * limit of query
     * @var integer
     */
    private $limit;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    function __construct(
        $filterConfig,
        SessionManagerInterface $sessionManager,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->sessionManager = $sessionManager;
        $this->webspaceManager = $webspaceManager;
        $this->filterConfig = $filterConfig;
    }

    public function build($languageCode)
    {
        // build sql2 query
        //
        // TODO We "should" be selecting from the mixin type "sulu:content"
        //      but this does not work with jackalope-doctrine-dbal at time of
        //      writing. The performance penalty for this alernative method in
        //      jackeabbit is apparently negligble or non-existant, but selecting
        //      from the mixin WOULD be quicker in doctrine-dbal when the feature is
        //      finally implemented.
        $sql2 = 'SELECT * FROM [nt:base] AS c WHERE c.[jcr:mixinTypes] = "sulu:content"';
        $sql2Where = $this->buildWhereClauses($languageCode);
        $sql2Order = $this->buildOrderClauses($languageCode);

        // append where clause to sql2 query
        if (!empty($sql2Where)) {
            $sql2 .= ' AND ' . join(' AND ', $sql2Where);
        }

        // append order clause
        if (!empty($sql2Order)) {
            $sortOrder = (isset($this->filterConfig['sortMethod']) && $this->filterConfig['sortMethod'] == 'asc')
                ? 'ASC' : 'DESC';
            $sql2 .= ' ORDER BY ' . join(', ', $sql2Order) . ' ' . $sortOrder;
        }

        // set limit if given
        if ($this->hasConfig('limitResult')) {
            $this->limit = $this->getConfig('limitResult');
        }

        return $sql2;
    }

    /**
     * returns limit of query
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * returns parent uuid of filtered nodes
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * build order clauses
     */
    private function buildOrderClauses($languageCode)
    {
        $sql2Order = array();
        $sortBy = $this->getConfig('sortBy', array());
        if (!empty($sortBy) && is_array($sortBy)) {
            foreach ($this->getConfig('sortBy', array()) as $sortColumn) {
                // TODO implement more generic
                $order = 'c.[i18n:' . $languageCode . '-' . $sortColumn . ']';
                if (!in_array($sortColumn, array('published', 'created', 'changed'))) {
                    $order = sprintf('lower(%s)', $order);
                }

                $sql2Order[] = $order;
            }
        }

        return $sql2Order;
    }

    /**
     * build where clauses
     */
    private function buildWhereClauses($languageCode)
    {
        $sql2Where = array();
        // build where clause for datasource
        if ($this->hasConfig('dataSource')) {
            $sql2Where[] = $this->getDatasource();
        }

        // build where clause for tags
        if ($this->hasConfig('tags')) {
            $sql2Where = array_merge($sql2Where, $this->getTags($languageCode));
        }

        // search only for published pages
        $sql2Where[] = 'c.[i18n:' . $languageCode . '-state] = ' . StructureInterface::STATE_PUBLISHED;

        return $sql2Where;
    }

    /**
     * build datasource where clause
     */
    private function getDatasource()
    {
        $dataSource = $this->getConfig('dataSource');
        $includeSubFolders = $this->getConfig('includeSubFolders', false);
        $sqlFunction = $includeSubFolders !== false && $includeSubFolders !== "false" ? 'ISDESCENDANTNODE' : 'ISCHILDNODE';

        if ($this->webspaceManager->findWebspaceByKey($dataSource) !== null) {
            $node = $this->sessionManager->getContentNode($dataSource);
            $this->parent = $node->getIdentifier();
        } else {
            $node = $this->sessionManager->getSession()->getNodeByIdentifier($dataSource);
            $this->parent = $dataSource;
        }

        return $sqlFunction . '(\'' . $node->getPath() . '\')';
    }

    /**
     * build tags where clauses
     */
    private function getTags($languageCode)
    {
        $sql2Where = array();
        foreach ($this->getConfig('tags', array()) as $tag) {
            $sql2Where[] = 'c.[i18n:' . $languageCode . '-excerpt-tags] = ' . $tag;
        }

        return $sql2Where;
    }

    /**
     * checks if config has given config name
     * @param string $name config name
     * @return boolean
     */
    private function hasConfig($name)
    {
        return isset($this->filterConfig[$name]);
    }

    /**
     * returns config value
     * @param string $name config name
     * @param mixed $default
     * @return mixed config value
     */
    private function getConfig($name, $default = null)
    {
        if (!$this->hasConfig($name)) {
            return $default;
        }

        return $this->filterConfig[$name];
    }
} 
