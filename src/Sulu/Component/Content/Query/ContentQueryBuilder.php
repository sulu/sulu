<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;

/**
 * Basic class for content query builder
 */
abstract class ContentQueryBuilder implements ContentQueryBuilderInterface
{
    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var MultipleTranslatedProperties
     */
    private $translatedProperties;

    /**
     * @var string[]
     */
    private $defaultProperties = array(
        'template',
        'changed',
        'changer',
        'created',
        'creator',
        'created',
        'nodeType',
        'state'
    );

    /**
     * @var string[]
     */
    protected $properties = array();

    /**
     * Only published content
     * @var bool
     */
    protected $published = true;

    /**
     * Load Excerpt data
     * @var bool
     */
    protected $excerpt = true;

    function __construct(StructureManagerInterface $structureManager, $languageNamespace)
    {
        $this->structureManager = $structureManager;
        $this->languageNamespace = $languageNamespace;

        $properties = array_merge($this->defaultProperties, $this->properties);
        $this->translatedProperties = new MultipleTranslatedProperties($properties, $this->languageNamespace);
    }

    /**
     * Returns translated property name
     */
    protected function getPropertyName($property)
    {
        return $this->translatedProperties->getName($property);
    }

    /**
     * Configures translated properties to given locale
     * @param string $locale
     */
    protected function setLocale($locale)
    {
        $this->translatedProperties->setLanguage($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function build($webspaceKey, $locales)
    {
        $additionalFields = array();

        $where = '';
        $select = array('route.*', 'page.*');
        $order = array();

        foreach ($locales as $locale) {
            $this->setLocale($locale);
            $additionalFields[$locale] = array();

            if ($this->excerpt) {
                $excerptSelect =  $this->buildSelectorForExcerpt($locale, $additionalFields);
                if($excerptSelect !== '') {
                    $select[] = $this->buildSelectorForExcerpt($locale, $additionalFields);
                }
            }

            $customSelect = $this->buildSelect($webspaceKey, $locale, $additionalFields);
            if ($customSelect !== '') {
                $select[] = $customSelect;
            }

            if ($this->published) {
                $where .= sprintf(
                    "%s (page.[%s] = %s ",
                    $where !== '' ? 'OR ' : '',
                    $this->getPropertyName('state'),
                    Structure::STATE_PUBLISHED
                );
            }

            $customWhere = $this->buildWhere($webspaceKey, $locale);
            if ($customWhere !== null && $customWhere !== '') {
                $where = $where . ($where !== '' ? ' AND ' : '') . $customWhere;
            }
            $where .= ')';

            $where .= sprintf(
                " AND (ISDESCENDANTNODE(route, '/cmf/%s/routes/%s') OR ISSAMENODE(route, '/cmf/%s/routes/%s') OR NOT (page.[%s:%s-nodeType] = '%s'))",
                $webspaceKey,
                $locale,
                $webspaceKey,
                $locale,
                $this->languageNamespace,
                $locale,
                Structure::NODE_TYPE_CONTENT
            );

            $customOrder = $this->buildOrder($webspaceKey, $locale);
            if (!empty($customOrder)) {
                $order[] = $customOrder;
            }
        }

        // build sql2 query string
        $sql2 = sprintf(
            "SELECT %s
             FROM [nt:unstructured] AS page
             LEFT OUTER JOIN [nt:unstructured] AS route ON page.[jcr:uuid] = route.[sulu:content]
             WHERE page.[jcr:mixinTypes] = 'sulu:content'
                AND (ISDESCENDANTNODE(page, '/cmf/%s/contents') OR ISSAMENODE(page, '/cmf/%s/contents'))
                AND (%s)
                %s %s",
            implode(', ', $select),
            $webspaceKey,
            $webspaceKey,
            $where,
            sizeof($order) > 0 ? 'ORDER BY' : '',
            implode(', ', $order)
        );

        return array($sql2, $additionalFields);
    }

    /**
     * Returns custom select statement
     */
    protected abstract function buildWhere($webspaceKey, $locale);

    /**
     * Returns custom where statement
     */
    protected abstract function buildSelect($webspaceKey, $locale, &$additionalFields);

    /**
     * Returns custom order statement
     */
    protected function buildOrder($webspaceKey, $locale)
    {
        return '';
    }

    /**
     * Returns select statement with all url and title properties
     */
    private function buildSelectForStructures($locale, $structures, &$names)
    {
        $result = '';
        // add node name and url to selector
        /** @var StructureInterface $structure */
        foreach ($structures as $structure) {
            $result .= $this->buildSelectForStructure($locale, $structure, $names);
        }

        return $result;
    }

    /**
     * Returns select of a single structure with title and url selector
     */
    private function buildSelectForStructure($locale, StructureInterface $structure, &$names)
    {
        $nodeNameProperty = $structure->getPropertyByTagName('sulu.node.name');
        $result = '';

        $name = $this->getTranslatedProperty($nodeNameProperty, $locale)->getName();
        if (!in_array($name, $names)) {
            $names[] = $name;
            $result .= ', ' . $this->buildSelector($name);
        }

        if ($structure->hasTag('sulu.rlp')) {
            $urlProperty = $structure->getPropertyByTagName('sulu.rlp');
            $name = $this->getTranslatedProperty($urlProperty, $locale)->getName();

            if ($urlProperty->getContentTypeName() !== 'resource_locator' && !in_array($name, $names)) {
                $names[] = $name;
                $result .= ', ' . $this->buildSelector($name);
            }
        }

        return $result;
    }

    /**
     * Returns a select statement for excerpt data
     */
    private function buildSelectorForExcerpt($locale, &$additionalFields)
    {
        $excerptStructure = $this->structureManager->getStructure('excerpt');
        $select = '';

        foreach ($excerptStructure->getProperties(true) as $property) {
            $column = sprintf('ext_excerpt_%s_%s', $locale, $property->getName());
            $additionalFields[$locale][] = array(
                'name' => $property->getName(),
                'column' => $column,
                'target' => 'excerpt',
                'property' => $property
            );

            $singleSelect = sprintf(
                '%s%s AS %s',
                ($select !== '') ? ', ' : '',
                $this->buildSelector($this->getTranslatedProperty($property, $locale)->getName()),
                $column
            );

            $select .= $singleSelect;
        }

        return $select;
    }

    /**
     * Returns single select statement
     */
    protected function buildSelector($name)
    {
        return sprintf("page.[%s]", $name);
    }

    /**
     * Returns a translated property
     */
    protected function getTranslatedProperty(PropertyInterface $property, $locale)
    {
        return new TranslatedProperty($property, $locale, $this->languageNamespace);
    }
}
