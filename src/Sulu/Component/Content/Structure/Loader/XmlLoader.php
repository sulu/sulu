<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Structure\Loader;

use Exception;
use Sulu\Component\Content\Template\Exception\RequiredPropertyNameNotFoundException;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Util\XmlUtils;
use DTL\Component\Content\Structure\Structure;
use DTL\Component\Content\Structure\Property;
use DTL\Component\Content\Structure\Item;
use DTL\Component\Content\Structure\Section;

/**
 * Load structure structure from an XML file
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
class XmlLoader implements LoaderInterface
{
    const SCHEME_PATH = '/schema/structure.xsd';

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $xmlDocument = XmlUtils::loadFile($resource, __DIR__ . static::SCHEME_PATH);

        // generate xpath for file
        $xpath = new \DOMXPath($xmlDocument);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/template/template');

        $structure = $this->loadStructure($xpath);
        $structure->resource = $resource;

        return $structure;
    }

    /**
     * load basic template attributes
     */
    private function loadStructure(\DOMXPath $xpath)
    {
        $structure = new Structure();
        $structure->name = $this->getValueFromXPath('/x:template/x:key', $xpath);
        $structure->parameters['template'] = $this->getValueFromXPath('/x:template/x:view', $xpath);
        $structure->parameters['controller'] = $this->getValueFromXPath('/x:template/x:controller', $xpath);
        $structure->parameters['cache_lifetime'] = $this->getValueFromXPath('/x:template/x:cacheLifetime', $xpath);
        $structure->tags = $this->loadStructureTags('/x:template/x:tag', $xpath);
        $structure->title = $this->loadTitle('/x:template/x:meta/x:title', $xpath);
        $structure->children = $this->loadItems('/x:template/x:properties/x:*', $tags, $xpath);

        return $structure;
    }

    private function loadTitle($path, $xpath, $context = null)
    {
        $titles = array();
        foreach ($xpath->query($path, $context) as $node) {
            $locale = $node->getAttribute('lang');
            $titles[$locale] = $node->nodeValue;
        }

        return $titles;
    }

    /**
     * load properties from given context
     */
    private function loadItems($path, &$tags, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $items = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $type = $node->tagName;

            switch ($type) {
                case 'property':
                    $item = $this->loadProperty($xpath, $node, $tags);
                    break;
                case 'block':
                    break;
                case 'section':
                    $item = $this->loadSection($xpath, $node, $tags);
                    break;
            }

            $items[$item->name] = $item;
        }

        return $items;
    }

    /**
     * load single property
     */
    private function loadProperty(\DOMXPath $xpath, \DOMNode $node, &$tags)
    {
        $property = new Property();
        $property->required = $this->getBooleanValueFromXPath('@mandatory', $xpath, $node, false);
        $property->localized = $this->getBooleanValueFromXPath('@multilingual', $xpath, $node, true);
        $property->tags = $this->loadTags('x:tag', $tags, $xpath, $node);
        $this->loadMetaForItem($property, $xpath, $node);

        $property->parameters = $this->loadParams('x:params/x:param', $xpath, $node);
        $property->name = $this->getValueFromXPath('@name', $xpath, $node);
        $property->type = $this->getValueFromXPath('@type', $xpath, $node);
        $property->minOccurs = $this->getValueFromXPath('@minOccurs', $xpath, $node);
        $property->maxOccurs = $this->getValueFromXPath('@maxOccurs', $xpath, $node);
        $property->colSpan = $this->getValueFromXPath('@colspan', $xpath, $node);

        $types = $this->loadTypes('x:types/x:type', $tags, $xpath, $node);

        if (count($types)) {
            $property->options['types'] = $types;
        }

        $cssClass = $this->getValueFromXPath('@cssClass', $xpath, $node);

        if ($cssClass) {
            $property['options']['cssClass'] = $cssClass;
        }

        $property->children  = $this->loadItems('x:properties/x:*', $tags, $xpath, $node);

        return $property;
    }

    private function loadMetaForItem(Item $property, \DOMXPath $xpath, \DOMNode $node)
    {
        $property->title = $this->loadTitle('x:meta/x:title', $xpath, $node);
        $property->description = $this->loadTitle('x:meta/x:info_text', $xpath, $node);
        $placeholder = $this->loadTitle('x:meta/x:info_text', $xpath, $node);

        if ($placeholder) {
            $property->parameters['placeholder'] = $placeholder;
        }
    }

    /**
     * Load a section
     *
     * @param \DOMXPath $xpath
     * @param \DOMNode $node
     * @param mixed $tags
     */
    private function loadSection(\DOMXPath $xpath, \DOMNode $node, &$tags)
    {
        $values = $this->loadValues(
            $xpath,
            $node,
            array('name', 'colspan', 'cssClass')
        );

        $section = new Section($values['name']);
        $section->parameters = $this->loadParams('x:params/x:param', $xpath, $node);
        $this->loadMetaForItem($section, $xpath, $node);
        $section->children = $this->loadItems('x:properties/x:*', $tags, $xpath, $node);

        return $section;
    }

    /**
     * load tags from given tag and validates them
     */
    private function loadTags($path, &$tags, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $tag = $this->loadTag($xpath, $node);
            $this->validateTag($tag, $tags);

            $result[] = $tag;
        }

        return $result;
    }

    /**
     * Loads the tags for the structure
     * @param $path
     * @param $xpath
     * @return array
     * @throws \InvalidArgumentException
     */
    private function loadStructureTags($path, $xpath)
    {
        $result = array();

        foreach ($xpath->query($path) as $node) {
            $tag = array(
                'name' => null,
                'attributes' => array(),
            );

            foreach ($node->attributes as $key => $attr) {
                if (in_array($key, array('name'))) {
                    $tag[$key] = $attr->value;
                } else {
                    $tag['attributes'][$key] = $attr->value;
                }
            }

            if (!isset($tag['name'])) {
                // this should not happen because of the XSD validation
                throw new \InvalidArgumentException('Tag does not have a name in template definition');
            }

            $result[] = $tag;
        }

        return $result;
    }

    /**
     * validates a single tag
     */
    private function validateTag($tag, &$tags)
    {
        if (!isset($tags[$tag['name']])) {
            $tags[$tag['name']] = array();
        }

        $tags[$tag['name']][] = $tag['priority'];
    }

    /**
     * load single tag
     */
    private function loadTag(\DOMXPath $xpath, \DOMNode $node)
    {
        $tag = array(
            'name' => null,
            'priority' => null,
            'attributes' => array(),
        );

        foreach ($node->attributes as $key => $attr) {
            if (in_array($key, array('name', 'priority'))) {
                $tag[$key] = $attr->value;
            } else {
                $tag['attributes'][$key] = $attr->value;
            }
        }

        return $tag;
    }

    /**
     * load params from given node
     */
    private function loadParams($path, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $results = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            list($name, $value) = $this->loadParam($xpath, $node);
            $results[$name] = $value;
        }

        return $results;
    }

    /**
     * load single param
     */
    private function loadParam(\DOMXPath $xpath, \DOMNode $node)
    {
        $name = $this->getValueFromXPath('@name', $xpath, $node, 'string');
        $type = $this->getValueFromXPath('@type', $xpath, $node, 'string');

        switch ($type) {
            case 'collection':
                $value = $this->loadParams('x:param', $xpath, $node);
                break;
            default:
                $value = $this->getValueFromXPath('@value', $xpath, $node, 'string');
                break;
        }

        return array($name, $value);
    }

    /**
     * load types from given node
     */
    private function loadTypes($path, &$tags, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $value = $this->loadType($xpath, $node, $tags);
            $result[$value['name']] = $value;
        }

        return $result;
    }

    /**
     * load single param
     */
    private function loadType(\DOMXPath $xpath, \DOMNode $node, &$tags)
    {
        $result = $this->loadValues($xpath, $node, array('name'));

        $result['meta'] = $this->loadMeta('x:meta/x:*', $xpath, $node);
        $result['properties'] = $this->loadProperties('x:properties/x:*', $tags, $xpath, $node);

        return $result;
    }

    private function loadMeta($path, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $attribute = $node->tagName;
            $lang = $this->getValueFromXPath('@lang', $xpath, $node);

            if (!isset($result[$node->tagName])) {
                $result[$attribute] = array();
            }
            $result[$attribute][$lang] = $node->textContent;
        }

        return $result;
    }

    /**
     * load values defined by key from given node
     */
    private function loadValues(\DOMXPath $xpath, \DOMNode $node, $keys, $prefix = '@')
    {
        $result = array();

        foreach ($keys as $key) {
            $result[$key] = $this->getValueFromXPath($prefix.$key, $xpath, $node);
        }

        return $result;
    }

    /**
     * returns boolean value of path
     */
    private function getBooleanValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null, $default = null)
    {
        if (($value = $this->getValueFromXPath($path, $xpath, $context)) != null) {
            return $value === 'true' ? true : false;
        } else {
            return $default;
        }
    }

    /**
     * returns value of path
     */
    private function getValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null, $default = null)
    {
        try {
            $result = $xpath->query($path, $context);
            if ($result->length === 0) {
                return $default;
            }

            $item = $result->item(0);
            if ($item === null) {
                return $default;
            }

            return $item->nodeValue;
        } catch (Exception $ex) {
            return $default;
        }
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
