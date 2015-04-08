<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure\Loader;

use Exception;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Util\XmlUtils;
use Sulu\Component\Content\Structure\Structure;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Content\Structure\Item;
use Sulu\Component\Content\Structure\Section;

/**
 * Load structure structure from an XML file
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class XmlLoader extends XmlLegacyLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $data = parent::load($resource, $type);
        $data = $this->normalizeStructureData($data);

        $structure = new Structure();
        $structure->name = $data['key'];
        $structure->title = $data['meta']['title'];
        $structure->description = $data['meta']['info_text'];
        $structure->cacheLifetime = $data['cacheLifetime'];
        $structure->controller = $data['controller'];
        $structure->tags = $data['tags'];
        $structure->parameters = $data['params'];

        foreach ($data['properties'] as $propertyName => $dataProperty) {
            $structure->children[$propertyName] = $this->createProperty($dataProperty);
        }

        return $structure;
    }

    private function createProperty($propertyData)
    {
        if ($propertyData['type'] === 'block') {
            return $this->createBlock($propertyData);
        }

        if ($propertyData['type'] === 'section') {
            return $this->createSection($propertyData);
        }

        $propertyData = $this->normalizePropertyData($propertyData);

        $property = new Property();
        $property->type = $propertyData['type'];
        $property->localized = $propertyData['multilingual'];
        $property->required = $propertyData['mandatory'];
        $property->colSpan = $propertyData['colspan'];
        $property->cssClass = $propertyData['cssClass'];
        $property->tags = $propertyData['tags'];
        $property->minOccurs = $propertyData['minOccurs'];
        $property->maxOccurs = $propertyData['maxOccurs'];

        return $property;
    }

    private function createSection($data)
    {
        $section = new Section();

        foreach ($data['properties'] as $name => $property) {
            $section->children[$name] = $this->createProperty($property);
        }

        return $section;
    }

    private function createBlock($data)
    {
        throw new \BadMethodCallException(sprintf(
            'Not implemented'
        ));
    }

    private function normalizePropertyData($data)
    {
        $data = array_merge_recursive(array(
            'type' => null,
            'multilingual' => true,
            'mandatory' => true,
            'colSpan' => null,
            'cssClass' => null,
            'minOccurs' => 1,
            'maxOccurs' => 1,
        ), $this->normalizeItem($data));

        return $data;
    }

    private function normalizeStructureData($data)
    {
        $data = array_merge_recursive(array(
            'key' => null,
            'view' => null,
            'controller' => null,
            'cacheLifetime' => null,
        ), $this->normalizeItem($data));

        return $data;
    }

    private function normalizeItem($data)
    {
        $data = array_merge_recursive(array(
            'meta' => array(
                'title' => array(),
                'info_text' => array(),
                'placeholder' => array(),
            ),
            'params' => array(),
            'tags' => array(),
        ), $data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        throw new FeatureNotImplementedException();
    }
}
