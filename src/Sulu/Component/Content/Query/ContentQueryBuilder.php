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
    private $structureManager;

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
    private $defaultProperties = array('template', 'changed', 'nodeType', 'state');

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
        $select = '';
        $names = array();

        foreach ($locales as $locale) {
            $this->setLocale($locale);
            $additionalFields[$locale] = array();

            if ($select != '') {
                $select .= ',';
            }
            // select internal properties
            $select .= sprintf(
                "route.[jcr:uuid] as routeUuid, route.[jcr:path] as routePath, page.[jcr:uuid], page.[jcr:path], page.[%s], page.[%s], page.[%s]",
                $this->getPropertyName('template'),
                $this->getPropertyName('changed'),
                $this->getPropertyName('nodeType')
            );

            $select .= $this->buildSelectForStructures($locale, $this->structureManager->getStructures(), $names);

            if ($this->excerpt) {
                $select .= ',' . $this->buildSelectorForExcerpt($locale, $additionalFields);
            }

            $select .= $this->buildSelect($webspaceKey, $locale, $additionalFields);

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
        }

        // build sql2 query string
        $sql2 = sprintf(
            "SELECT route.*, page.*, %s
             FROM [nt:unstructured] AS page
             LEFT OUTER JOIN [nt:unstructured] AS route ON page.[jcr:uuid] = route.[sulu:content]
             WHERE page.[jcr:mixinTypes] = 'sulu:content'
                AND (ISDESCENDANTNODE(page, '/cmf/%s/contents') OR ISSAMENODE(page, '/cmf/%s/contents'))
                AND (%s)",
            $select,
            $webspaceKey,
            $webspaceKey,
            $where
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
                'target' => 'excerpt'
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
    private function buildSelector($name)
    {
        return sprintf("page.[%s]", $name);
    }

    /**
     * Returns a translated property
     */
    private function getTranslatedProperty(PropertyInterface $property, $locale)
    {
        return new TranslatedProperty($property, $locale, $this->languageNamespace);
    }
}
