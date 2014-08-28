<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;

class MinimumContentQueryBuilder
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var string
     */
    private $languageNamespace;

    function __construct(StructureManagerInterface $structureManager, $languageNamespace)
    {
        $this->languageNamespace = $languageNamespace;
        $this->structureManager = $structureManager;
    }

    public function build($webspaceKey, $locales)
    {
        // init internal properties
        $translatedProperties = new MultipleTranslatedProperties(
            array('template', 'changed', 'nodeType'), $this->languageNamespace
        );
        // init select / where
        $select = '';
        $where = '';

        foreach ($locales as $locale) {
            $translatedProperties->setLanguage($locale);

            if ($select != '') {
                $select .= ',';
            }
            // select internal properties
            $select .= sprintf(
                "route.[jcr:uuid] as routeUuid, page.[jcr:uuid], page.[%s], page.[%s], page.[%s]",
                $translatedProperties->getName('template'),
                $translatedProperties->getName('changed'),
                $translatedProperties->getName('nodeType')
            );

            $select .= $this->buildSelectForStructures($locale, $this->structureManager->getStructures());

            // where state published
            $where .= sprintf(
                "%spage.[i18n:%s-state] = %s",
                $where !== '' ? 'OR ' : '',
                $locale,
                Structure::STATE_PUBLISHED
            );
        }

        // build sql2 query string
        $sql2 = sprintf(
            "SELECT route.*, page.*, %s
             FROM [nt:unstructured] AS page LEFT OUTER JOIN [nt:unstructured] AS route ON page.[jcr:uuid] = route.[sulu:content]
             WHERE page.[jcr:mixinTypes] = 'sulu:content' AND (%s) AND ISDESCENDANTNODE(page, '/cmf/%s/contents')",
            $select,
            $where,
            $webspaceKey
        );

        return $sql2;
    }

    /**
     * Returns select statement
     * @param string $locale
     * @param StructureInterface[] $structures
     * @return string
     */
    private function buildSelectForStructures($locale, $structures)
    {
        $names = array();
        $result = '';
        // add node name and url to selector
        /** @var StructureInterface $structure */
        foreach ($structures as $structure) {
            $result .= $this->buildSelectForStructure($locale, $structure, $names);
        }

        return $result;
    }

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

    private function buildSelector($name)
    {
        return sprintf("page.[%s]", $name);
    }

    /**
     * Returns a translated property
     * @param PropertyInterface $property
     * @param string $locale
     * @return PropertyInterface
     */
    private function getTranslatedProperty(PropertyInterface $property, $locale)
    {
        return new TranslatedProperty($property, $locale, $this->languageNamespace);
    }
} 
