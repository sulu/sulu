<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\SmartContent;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Query\ContentQueryBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Query builder to load smart content.
 */
class QueryBuilder extends ContentQueryBuilder
{
    /**
     * disable automatic excerpt loading.
     *
     * @var bool
     */
    protected $excerpt = false;

    /**
     * configuration which properties should be loaded.
     *
     * @var array
     */
    private $propertiesConfig = [];

    /**
     * configuration of.
     *
     * @var array
     */
    private $config = [];

    /**
     * array of ids to load.
     *
     * @var array
     */
    private $ids = [];

    /**
     * array of excluded pages.
     *
     * @var array
     */
    private $excluded = [];

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        StructureManagerInterface $structureManager,
        WebspaceManagerInterface $webspaceManager,
        SessionManagerInterface $sessionManager,
        $languageNamespace
    ) {
        parent::__construct($structureManager, $languageNamespace);

        $this->webspaceManager = $webspaceManager;
        $this->sessionManager = $sessionManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        $sql2Where = [];
        // build where clause for datasource
        if ($this->hasConfig('dataSource')) {
            $sql2Where[] = $this->buildDatasourceWhere();
        } elseif (count($this->ids) === 0) {
            $sql2Where[] = sprintf(
                'ISDESCENDANTNODE(page, "/cmf/%s/contents")',
                $webspaceKey
            );
        }

        // build where clause for tags
        if ($this->hasConfig('tags')) {
            $sql2Where[] = $this->buildTagsWhere(
                $this->getConfig('tags', []),
                $this->getConfig('tagOperator', 'OR'),
                $locale
            );
        }

        // build where clause for website tags
        if ($this->hasConfig('websiteTags')) {
            $sql2Where[] = $this->buildTagsWhere(
                $this->getConfig('websiteTags', []),
                $this->getConfig('websiteTagOperator', 'OR'),
                $locale
            );
        }

        // build where clause for categories
        if ($this->hasConfig('categories')) {
            $sql2Where[] = $this->buildCategoriesWhere(
                $this->getConfig('categories', []),
                $this->getConfig('categoryOperator', 'OR'),
                $locale
            );
        }

        // build where clause for website categories
        if ($this->hasConfig('categories')) {
            $sql2Where[] = $this->buildCategoriesWhere(
                $this->getConfig('websiteCategories', []),
                $this->getConfig('websiteCategoryOperator', 'OR'),
                $locale
            );
        }

        if (count($this->ids) > 0) {
            $sql2Where[] = $this->buildPageSelector();
        }

        if (count($this->excluded) > 0) {
            $sql2Where = array_merge($sql2Where, $this->buildPageExclude());
        }

        $sql2Where = array_filter($sql2Where);

        return implode(' AND ', $sql2Where);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        $select = [];

        if (count($this->propertiesConfig) > 0) {
            $this->buildPropertiesSelect($locale, $additionalFields);
        }

        return implode(', ', $select);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOrder($webspaceKey, $locale)
    {
        $sortOrder = (isset($this->config['sortMethod']) && strtolower($this->config['sortMethod']) === 'desc')
            ? 'DESC' : 'ASC';

        $sql2Order = [];
        $sortBy = $this->getConfig('sortBy', []);

        if (!empty($sortBy) && is_array($sortBy)) {
            foreach ($sortBy as $sortColumn) {
                // TODO implement more generic
                $order = 'page.[i18n:' . $locale . '-' . $sortColumn . '] ';
                if (!in_array($sortColumn, ['published', 'created', 'changed'])) {
                    $order = sprintf('lower(%s)', $order);
                }

                $sql2Order[] = $order . ' ' . $sortOrder;
            }
        } else {
            $sql2Order[] = 'page.[sulu:order] ' . $sortOrder;
        }

        return implode(', ', $sql2Order);
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options)
    {
        $this->propertiesConfig = isset($options['properties']) ? $options['properties'] : [];
        $this->ids = isset($options['ids']) ? $options['ids'] : [];
        $this->config = isset($options['config']) ? $options['config'] : [];
        $this->excluded = isset($options['excluded']) ? $options['excluded'] : [];
    }

    /**
     * build select for properties.
     */
    private function buildPropertiesSelect($locale, &$additionalFields)
    {
        foreach ($this->propertiesConfig as $parameter) {
            $alias = $parameter->getName();
            $propertyName = $parameter->getValue();

            if (strpos($propertyName, '.') !== false) {
                $parts = explode('.', $propertyName);

                $this->buildExtensionSelect($alias, $parts[0], $parts[1], $locale, $additionalFields);
            } else {
                $this->buildPropertySelect($alias, $propertyName, $locale, $additionalFields);
            }
        }
    }

    /**
     * build select for single property.
     */
    private function buildPropertySelect($alias, $propertyName, $locale, &$additionalFields)
    {
        foreach ($this->structureManager->getStructures(Structure::TYPE_PAGE) as $structure) {
            if ($structure->hasProperty($propertyName)) {
                $property = $structure->getProperty($propertyName);
                $additionalFields[$locale][] = [
                    'name' => $alias,
                    'property' => $property,
                    'templateKey' => $structure->getKey(),
                ];
            }
        }
    }

    /**
     * build select for extension property.
     */
    private function buildExtensionSelect($alias, $extension, $propertyName, $locale, &$additionalFields)
    {
        $extension = $this->structureManager->getExtension('all', $extension);
        $additionalFields[$locale][] = [
            'name' => $alias,
            'extension' => $extension,
            'property' => $propertyName,
        ];
    }

    /**
     * build datasource where clause.
     */
    private function buildDatasourceWhere()
    {
        $dataSource = $this->getConfig('dataSource');
        $includeSubFolders = $this->getConfig('includeSubFolders', false);
        $sqlFunction = $includeSubFolders !== false && $includeSubFolders !== 'false' ?
            'ISDESCENDANTNODE' : 'ISCHILDNODE';

        if ($this->webspaceManager->findWebspaceByKey($dataSource) !== null) {
            $node = $this->sessionManager->getContentNode($dataSource);
        } else {
            $node = $this->sessionManager->getSession()->getNodeByIdentifier($dataSource);
        }

        return $sqlFunction . '(page, \'' . $node->getPath() . '\')';
    }

    /**
     * build tags where clauses.
     */
    private function buildTagsWhere($tags, $operator, $languageCode)
    {
        $structure = $this->structureManager->getStructure('excerpt');

        $sql2Where = [];
        if ($structure->hasProperty('tags')) {
            $property = new TranslatedProperty(
                $structure->getProperty('tags'),
                $languageCode,
                $this->languageNamespace,
                'excerpt'
            );

            foreach ($tags as $tag) {
                $sql2Where[] = 'page.[' . $property->getName() . '] = ' . $tag;
            }

            if (count($sql2Where) > 0) {
                return '(' . implode(' ' . strtoupper($operator) . ' ', $sql2Where) . ')';
            }
        }

        return '';
    }

    /**
     * build categories where clauses.
     */
    private function buildCategoriesWhere($categories, $operator, $languageCode)
    {
        $structure = $this->structureManager->getStructure('excerpt');

        $sql2Where = [];
        if ($structure->hasProperty('categories')) {
            $property = new TranslatedProperty(
                $structure->getProperty('categories'),
                $languageCode,
                $this->languageNamespace,
                'excerpt'
            );
            foreach ($categories as $category) {
                $sql2Where[] = 'page.[' . $property->getName() . '] = ' . $category;
            }

            if (count($sql2Where) > 0) {
                return '(' . implode(' ' . strtoupper($operator) . ' ', $sql2Where) . ')';
            }
        }

        return '';
    }

    /**
     * checks if config has given config name.
     *
     * @param string $name config name
     *
     * @return bool
     */
    private function hasConfig($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * returns config value.
     *
     * @param string $name    config name
     * @param mixed  $default
     *
     * @return mixed config value
     */
    private function getConfig($name, $default = null)
    {
        if (!$this->hasConfig($name)) {
            return $default;
        }

        return $this->config[$name];
    }

    /**
     * build select for uuids.
     */
    protected function buildPageSelector()
    {
        $idsWhere = [];
        foreach ($this->ids as $id) {
            $idsWhere[] = sprintf("page.[jcr:uuid] = '%s'", $id);
        }

        return '(' . implode(' OR ', $idsWhere) . ')';
    }

    /**
     * build sql for exluded Pages.
     */
    private function buildPageExclude()
    {
        $idsWhere = [];
        foreach ($this->excluded as $id) {
            $idsWhere[] = sprintf("NOT (page.[jcr:uuid] = '%s')", $id);
        }

        return $idsWhere;
    }
}
