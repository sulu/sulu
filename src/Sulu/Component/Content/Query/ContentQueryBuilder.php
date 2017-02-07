<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;

/**
 * Basic class for content query builder.
 */
abstract class ContentQueryBuilder implements ContentQueryBuilderInterface
{
    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

    /**
     * @var string
     */
    protected $languageNamespace;

    /**
     * @var MultipleTranslatedProperties
     */
    private $translatedProperties;

    /**
     * @var string[]
     */
    private $defaultProperties = [
        'template',
        'changed',
        'changer',
        'created',
        'creator',
        'created',
        'nodeType',
        'state',
        'shadow-on',
    ];

    /**
     * @var string[]
     */
    protected $properties = [];

    /**
     * Only published content.
     *
     * @var bool
     */
    protected $published = true;

    /**
     * Load Excerpt data.
     *
     * @var bool
     */
    protected $excerpt = true;

    public function __construct(
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        $languageNamespace
    ) {
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->languageNamespace = $languageNamespace;

        $properties = array_merge($this->defaultProperties, $this->properties);
        $this->translatedProperties = new MultipleTranslatedProperties($properties, $this->languageNamespace);
    }

    /**
     * Returns translated property name.
     */
    protected function getPropertyName($property)
    {
        return $this->translatedProperties->getName($property);
    }

    /**
     * Configures translated properties to given locale.
     *
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
        $additionalFields = [];

        $where = '';
        $select = ['page.*'];
        $order = [];

        foreach ($locales as $locale) {
            $this->setLocale($locale);
            $additionalFields[$locale] = [];

            if ($this->excerpt) {
                $this->buildSelectorForExcerpt($locale, $additionalFields);
            }

            $customSelect = $this->buildSelect($webspaceKey, $locale, $additionalFields);
            if ($customSelect !== '') {
                $select[] = $customSelect;
            }

            if ($this->published) {
                $where .= sprintf(
                    '%s ((page.[%s] = %s OR page.[%s] = %s)',
                    $where !== '' ? 'OR ' : '',
                    $this->getPropertyName('state'),
                    Structure::STATE_PUBLISHED,
                    $this->getPropertyName('shadow-on'),
                    'true'
                );
            }

            $customWhere = $this->buildWhere($webspaceKey, $locale);
            if ($customWhere !== null && $customWhere !== '') {
                $where = $where . ($where !== '' ? ' AND ' : '') . $customWhere;
            }

            if ($this->published) {
                $where .= ')';
            }

            $customOrder = $this->buildOrder($webspaceKey, $locale);
            if (!empty($customOrder)) {
                $order[] = $customOrder;
            } else {
                $order = ['[jcr:path] ASC'];
            }
        }

        // build sql2 query string
        $sql2 = sprintf(
            "SELECT %s
             FROM [nt:unstructured] AS page
             WHERE (page.[jcr:mixinTypes] = 'sulu:page' OR page.[jcr:mixinTypes] = 'sulu:home')
                AND (%s)
                %s %s",
            implode(', ', $select),
            $where,
            count($order) > 0 ? 'ORDER BY' : '',
            implode(', ', $order)
        );

        return [$sql2, $additionalFields];
    }

    /**
     * {@inheritdoc}
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Returns custom select statement.
     */
    abstract protected function buildWhere($webspaceKey, $locale);

    /**
     * Returns custom where statement.
     */
    abstract protected function buildSelect($webspaceKey, $locale, &$additionalFields);

    /**
     * Returns custom order statement.
     */
    protected function buildOrder($webspaceKey, $locale)
    {
        return '';
    }

    /**
     * Returns select statement with all url and title properties.
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
     * Returns select of a single structure with title and url selector.
     */
    private function buildSelectForStructure($locale, StructureInterface $structure, &$names)
    {
        $nodeNameProperty = $structure->getProperty('title');
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
     * Returns a select statement for excerpt data.
     */
    private function buildSelectorForExcerpt($locale, &$additionalFields)
    {
        $excerptStructure = $this->structureManager->getStructure('excerpt');
        $extension = $this->extensionManager->getExtension('', 'excerpt');

        foreach ($excerptStructure->getProperties(true) as $property) {
            $additionalFields[$locale][] = [
                'extension' => $extension,
                'target' => 'excerpt',
                'property' => $property->getName(),
                'name' => $property->getName(),
            ];
        }
    }

    /**
     * Returns single select statement.
     */
    protected function buildSelector($name)
    {
        return sprintf('page.[%s]', $name);
    }

    /**
     * Returns a translated property.
     */
    protected function getTranslatedProperty(PropertyInterface $property, $locale)
    {
        return new TranslatedProperty($property, $locale, $this->languageNamespace);
    }
}
