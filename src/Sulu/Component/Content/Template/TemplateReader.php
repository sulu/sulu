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
    private $pathToProperties = '/x:template/x:properties/*';

    /**
     * @var string
     */
    private $pathToParams = 'x:params/x:param';

    /**
     * @var \DOMDocument
     */
    private $xmlDocument;

    /**
     * @var array
     */
    private $complexNodeTypes = array('block');

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

        $template = array();
        $this->xmlDocument = new \DOMDocument();

        try {
            $this->xmlDocument->load($path);

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

                if(in_array($node->tagName, $this->complexNodeTypes)) {
                    $attributes['type'] = $node->tagName;
                }

                $name = $attributes[$this->nameKey];
                $params = $this->getChildrenOfNode($node, $this->pathToParams, $xpath);
                $template[$this->propertiesKey][$name] = array_merge($attributes, $params);

                if(in_array($node->tagName, $this->complexNodeTypes)) {
                    $template[$this->propertiesKey][$name][$this->propertiesKey] = $this->parseSubproperties($xpath,$node);
                }

            }

        } catch (InvalidXmlException $ex) {
            throw $ex;
        } catch (Exception $ex) {
            // TODO do not catch exceptions here but in the callee
            throw new InvalidArgumentException('Path is invalid: ' . $path);
        }

        return $template;
    }

    /**
     * Parses childnodes and its attributes recursively and puts them into the properties element
     * @param $xpath
     * @param \DOMNode $node
     * @return array with sub properties
     */
    private function parseSubproperties($xpath, $node){

        $properties = array();

        /** @var \DOMNodeList $children */
        $children = $xpath->query('x:properties/*', $node);


        /** @var \DOMNode $child */
        foreach($children as $child) {

            $attributes = $this->getAllAttributesOfNode($child);

            if(in_array($child->tagName, $this->complexNodeTypes)) {
                $attributes['type'] = $child->tagName;
            }

            $name = $attributes[$this->nameKey];
            $params = $this->getChildrenOfNode($child, $this->pathToParams, $xpath);
            $properties[$name] = array_merge($attributes, $params);

            if(in_array($child->tagName, $this->complexNodeTypes)) {
                $properties[$name][$this->propertiesKey] = $this->parseSubproperties($xpath,$child);
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
                    $value = $value + 0;
                } else {
                    if ($value === 'true') {
                        $value = true;
                    } else {
                        if ($value === 'false') {
                            $value = false;
                        }
                    }
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
     * @return array
     * @internal param $paramsTag
     */
    private function getChildrenOfNode(\DOMNode $node, $path, $xpath)
    {

        $keyValue = array();
        $params = $children = $xpath->query($path, $node);

        if ($params->length > 0) {

            $keyValue[$this->paramsKey] = array();
            $xpath = new \DOMXPath($this->xmlDocument);
            $xpath->registerNamespace('x', 'http://schemas.sulu.io/template/template');

            $children = $xpath->query($path, $node);

            foreach ($children as $child) {
                $keyValue[$this->paramsKey] = array_merge(
                    $keyValue[$this->paramsKey],
                    $this->getAttributesAsKeyValuePairs($child)
                );
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

        $keyValues = array();

        if ($node->hasAttributes()) {
            $keyValues[$node->attributes->item(0)->nodeValue] = $node->attributes->item(1)->nodeValue;
        }

        return $keyValues;

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
     * @param string $type     The resource type
     *
     * @throws \Sulu\Exception\FeatureNotImplementedException
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        // TODO
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
