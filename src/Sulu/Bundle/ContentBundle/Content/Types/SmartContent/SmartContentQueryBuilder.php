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
    private $propertyConfig = array();

    /**
     * configuration which extension properties should be loaded
     * @var array
     */
    private $extensionConfig = array();

    /**
     * {@inheritdoc}
     */
    protected function buildWhere($webspaceKey, $locale)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildSelect($webspaceKey, $locale, &$additionalFields)
    {
        $select = array();

        if (sizeof($this->propertyConfig) > 0) {
            $select[] = $this->buildPropertiesSelect($locale, $additionalFields);
        }

        if (sizeof($this->extensionConfig) > 0) {
            // $select[] = $this->buildExtensionsSelect($webspaceKey, $locale, $additionalFields);
        }

        return implode(', ', $select);
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options)
    {
        $this->propertyConfig = isset($options['properties']) ? $options['properties'] : array();
        $this->extensionConfig = isset($options['extension']) ? $options['extension'] : array();
    }

    private function buildPropertiesSelect($locale, &$additionalFields)
    {
        $select = array();
        foreach ($this->propertyConfig as $alias => $propertyName) {
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

    private function buildExtensionsSelect($webspaceKey, $locale, &$additionalFields)
    {
        return '';
    }
}
