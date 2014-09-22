<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types\SmartContent;

use Sulu\Component\Content\Query\ContentQueryBuilder;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Query builder to load smart content
 */
class SmartContentQueryBuilder extends ContentQueryBuilder
{
    /**
     * disable automatic excerpt loading
     * @var bool
     */
    protected $excerpt = false;

    /**
     * configuration which properties should be loaded
     * @var array
     */
    private $propertiesConfig = array();

    /**
     * configuration which extension properties should be loaded
     * @var array
     */
    private $extensionsConfig = array();

    /**
     * configuration of
     * @var array
     */
    private $config = array();

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    function __construct(
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
        $sql2Where = array();
        // build where clause for datasource
        if ($this->hasConfig('dataSource')) {
            $sql2Where[] = $this->buildDatasourceWhere();
        }

        // build where clause for tags
        if ($this->hasConfig('tags')) {
            $sql2Where = array_merge($sql2Where, $this->buildTagsWhere($locale));
        }

        return implode(' AND ', $sql2Where);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        $select = array();

        if (sizeof($this->propertiesConfig) > 0) {
            $select[] = $this->buildPropertiesSelect($locale, $additionalFields);
        }

        if (sizeof($this->extensionsConfig) > 0) {
            // $select[] = $this->buildExtensionsSelect($webspaceKey, $locale, $additionalFields);
        }

        return implode(', ', $select);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildOrder($webspaceKey, $locale)
    {
        $sortOrder = (isset($this->config['sortMethod']) && $this->config['sortMethod'] == 'asc')
            ? 'ASC' : 'DESC';

        $sql2Order = array();
        $sortBy = $this->getConfig('sortBy', array());
        if (!empty($sortBy) && is_array($sortBy)) {
            foreach ($sortBy as $sortColumn) {
                // TODO implement more generic
                $order = 'page.[i18n:' . $locale . '-' . $sortColumn . '] ';
                if (!in_array($sortColumn, array('published', 'created', 'changed'))) {
                    $order = sprintf('lower(%s)', $order);
                }

                $sql2Order[] = $order . ' ' . $sortOrder;
            }
        }

        return implode(', ', $sql2Order);
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options)
    {
        $this->propertiesConfig = isset($options['properties']) ? $options['properties'] : array();
        $this->extensionsConfig = isset($options['extension']) ? $options['extension'] : array();
        $this->config = isset($options['config']) ? $options['config'] : array();
    }

    private function buildPropertiesSelect($locale, &$additionalFields)
    {
        $select = array();
        foreach ($this->propertiesConfig as $alias => $propertyName) {
            $select[] = $this->buildPropertySelect($alias, $propertyName, $locale, $additionalFields);
        }

        return implode(', ', $select);
    }

    private function buildPropertySelect($alias, $propertyName, $locale, &$additionalFields)
    {
        $select = array();
        foreach ($this->structureManager->getStructures() as $structure) {
            if ($structure->hasProperty($propertyName)) {
                $property = $structure->getProperty($propertyName);
                $additionalFields[$locale][] = array(
                    'name' => $alias,
                    'property' => $property,
                    'templateKey' => $structure->getKey()
                );
            }
        }

        return implode(', ', $select);
    }

    /**
     * build datasource where clause
     */
    private function buildDatasourceWhere()
    {
        $dataSource = $this->getConfig('dataSource');
        $includeSubFolders = $this->getConfig('includeSubFolders', false);
        $sqlFunction = $includeSubFolders !== false && $includeSubFolders !== "false" ? 'ISDESCENDANTNODE' : 'ISCHILDNODE';

        if ($this->webspaceManager->findWebspaceByKey($dataSource) !== null) {
            $node = $this->sessionManager->getContentNode($dataSource);
        } else {
            $node = $this->sessionManager->getSession()->getNodeByIdentifier($dataSource);
        }

        return $sqlFunction . '(page, \'' . $node->getPath() . '\')';
    }

    /**
     * build tags where clauses
     */
    private function buildTagsWhere($languageCode)
    {
        $sql2Where = array();
        foreach ($this->getConfig('tags', array()) as $tag) {
            $sql2Where[] = 'page.[i18n:' . $languageCode . '-excerpt-tags] = ' . $tag;
        }

        return $sql2Where;
    }

    /**
     * build select for extensions
     */
    private function buildExtensionsSelect($webspaceKey, $locale, &$additionalFields)
    {
        return '';
    }

    /**
     * checks if config has given config name
     * @param string $name config name
     * @return boolean
     */
    private function hasConfig($name)
    {
        return isset($this->config[$name]);
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

        return $this->config[$name];
    }
}
