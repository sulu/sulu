<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Component\Content\Template;

use Exception;
use Sulu\Exception\FeatureNotImplementedException;
use Sulu\Component\Content\Template\Exception\InvalidXmlException;
use Sulu\Component\Content\Template\Exception\InvalidArgumentException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;

/**
 * reads a template xml and returns a array representation
 */
class TemplateReader implements LoaderInterface
{
    /**
     * @var string
     */
    private $nameKey = 'name';

    /**
     * @var string
     */
    private $propertiesKey = 'properties';

    /**
     * @var string
     */
    private $paramsKey = 'params';

    /**
     * @var string
     */
    private $tagKey = 'tags';

    /**
     * @var string
     */
    private $pathToProperties = '/x:template/x:properties/*';

    /**
     * @var string
     */
    private $pathToParams = 'x:params/x:param';

    /**
     * @var string
     */
    private $pathToTags = 'x:tag';

    /**
     * @var \DOMDocument
     */
    private $xmlDocument;

    /**
     * @var array
     */
    private $complexNodeTypes = array('block');

    /**
     * @var array
     */
    private $tags = array();

    /**
     * @var array
     */
    private $requiredTags = array(
        'sulu.node.name'
    );

    /**
     * Reads all types from the given path
     * @param $path string path to file with type definitions
     * @param $mandatoryNodes array with key of mandatory node names
     * @throws \Sulu\Component\Content\Template\Exception\InvalidXmlException
     * @throws \Sulu\Component\Content\Template\Exception\InvalidArgumentException
     * @return array with found definitions of types
     */
    private function readTemplate($path, $mandatoryNodes = array('key', 'view', 'controller', 'cacheLifetime'))
    {
        $this->tags = array();
        $template = array();
        $this->xmlDocument = new \DOMDocument();

        try {
            $this->xmlDocument->load($path);
        } catch (InvalidXmlException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            // TODO do not catch exceptions here but in the callee
            throw new InvalidArgumentException('Path is invalid: ' . $path);
        }

        if (!empty($mandatoryNodes)) {
            $template = $this->getMandatoryNodes($mandatoryNodes);
        }

        $template[$this->propertiesKey] = array();
        $xpath = new \DOMXPath($this->xmlDocument);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/template/template');

        /** @var \DOMNodeList $nodes */
        $nodes = $xpath->query($this->pathToProperties);

        foreach ($nodes as $node) {

            /** @var \DOMNode $node */
            $attributes = $this->getAllAttributesOfNode($node);

            if (in_array($node->tagName, $this->complexNodeTypes)) {
                $attributes['type'] = $node->tagName;
            }

            $name = $attributes[$this->nameKey];
            $params = $this->getChildrenOfNode($node, $this->pathToParams, $xpath, $this->paramsKey);
            $tags = $this->getChildrenOfNode($node, $this->pathToTags, $xpath, $this->tagKey);
            $this->tags = array_merge($this->tags, (isset($tags['tags']) ? $tags['tags'] : array()));
            $template[$this->propertiesKey][$name] = array_merge($attributes, $tags, $params);

            if (in_array($node->tagName, $this->complexNodeTypes)) {
                $template[$this->propertiesKey][$name][$this->propertiesKey] = $this->parseSubproperties($xpath, $node);
            }
        }

        // check combination of tag and priority of uniqueness
        // check required properties
        $required = array_merge(array(), $this->requiredTags);
        for ($x = 0; $x < sizeof($this->tags); $x++) {
            // check required properties
            for ($y = 0; $y < sizeof($required); $y++) {
                if ($required[$y] === $this->tags[$x]['name']) {
                    break;
                }
            }
            unset($required[$y]);

            // extract name and prio
            $xName = $this->tags[$x]['name'];
            $xPriority = isset($this->tags[$x]['priority']) ? $this->tags[$x]['priority'] : 1;
            for ($y = 0; $y < sizeof($this->tags); $y++) {
                // extract name and prio
                $yName = $this->tags[$y]['name'];
                $yPriority = isset($this->tags[$y]['priority']) ? $this->tags[$y]['priority'] : 1;
                // check of uniqueness
                if ($x !== $y && $xName === $yName && $xPriority === $yPriority) {
                    throw new InvalidXmlException(sprintf(
                        'Priority %s of tag %s exists duplicated',
                        $xPriority,
                        $xName
                    ));
                }
            }
        }

        // throw exception if not all required tags are set
        if (sizeof($required) > 0) {
            throw new InvalidXmlException(sprintf(
                'Tag(s) %s required but not found',
                join(',', $required)
            ));
        }

        return $template;
    }

    /**
     * Parses childnodes and its attributes recursively and puts them into the properties element
     * @param $xpath
     * @param \DOMNode $node
     * @return array with sub properties
     */
    private function parseSubproperties($xpath, $node)
    {

        $properties = array();

        /** @var \DOMNodeList $children */
        $children = $xpath->query('x:properties/*', $node);


        /** @var \DOMNode $child */
        foreach ($children as $child) {

            $attributes = $this->getAllAttributesOfNode($child);

            if (in_array($child->tagName, $this->complexNodeTypes)) {
                $attributes['type'] = $child->tagName;
            }

            $name = $attributes[$this->nameKey];
            $params = $this->getChildrenOfNode($child, $this->pathToParams, $xpath, $this->paramsKey);
            $tags = $this->getChildrenOfNode($child, $this->pathToTags, $xpath, $this->tagKey);
            $properties[$name] = array_merge($attributes, $tags, $params);

            if (in_array($child->tagName, $this->complexNodeTypes)) {
                $properties[$name][$this->propertiesKey] = $this->parseSubproperties($xpath, $child);
            }

        }

        return $properties;
    }

    /**
     * Get values of mandatory fields
     * @param $mandatoryNodes
     * @throws InvalidXmlException
     * @return array with mandatory field-keys and -values
     */
    private function getMandatoryNodes($mandatoryNodes)
    {
        $mandatoryFields = array();

        foreach ($mandatoryNodes as $node) {
            try {
                $value = $this->xmlDocument->getElementsByTagName($node)->item(0)->nodeValue;
                $mandatoryFields[$node] = $value;
            } catch (Exception $ex) {
                throw new InvalidXmlException('Missing or empty mandatory node in xml!');
            }
        }

        return $mandatoryFields;
    }

    /**
     * Returns attributes form a node
     * @param \DOMNode $node
     * @return array
     */
    private function getAllAttributesOfNode(\DOMNode $node)
    {
        $attributes = array();

        /** @var \DOMElement $node */
        if ($node->hasAttributes()) {
            for ($i = 0; $i < $node->attributes->length; $i++) {
                $value = $node->attributes->item($i)->nodeValue;

                if (is_numeric($value)) {
                    $value = (int) $value;
                } elseif ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }

                $attributes[$node->attributes->item($i)->nodeName] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Returns an array with all the attributes from the children of a node
     * @param \DOMNode $node
     * @param $path
     * @param $xpath
     * @param $name
     * @return array
     */
    private function getChildrenOfNode(\DOMNode $node, $path, $xpath, $name)
    {
        $keyValue = array();
        $items = $xpath->query($path, $node);

        if ($items->length > 0) {
            $keyValue[$name] = array();
            foreach ($items as $child) {
                $keyValue[$name][] = $this->getAttributesAsKeyValuePairs($child);
            }
        }

        return $keyValue;
    }

    /**
     * Returns attributes as key value pairs (e.g. for params)
     * @param \DOMNode $node
     * @return array
     */
    private function getAttributesAsKeyValuePairs(\DOMNode $node)
    {
        $values = array();
        if ($node->hasAttributes()) {
            for ($i = 0; $i < $node->attributes->length; $i++) {
                $attr = $node->attributes->item($i);
                $values[$attr->nodeName] = $attr->nodeValue;
            }
        }
        return $values;
    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string $type The resource type
     * @return array
     */
    public function load($resource, $type = null)
    {
        return $this->readTemplate($resource);
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type The resource type
     *
     * @throws \Sulu\Exception\FeatureNotImplementedException
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * Gets the loader resolver.
     *
     * @throws \Sulu\Exception\FeatureNotImplementedException
     * @return LoaderResolverInterface A LoaderResolverInterface instance
     */
    public function getResolver()
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * Sets the loader resolver.
     *
     * @param LoaderResolverInterface $resolver A LoaderResolverInterface instance
     * @throws \Sulu\Exception\FeatureNotImplementedException
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        throw new FeatureNotImplementedException();
    }

}
