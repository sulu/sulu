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
use Gedmo\Exception\FeatureNotImplementedException;
use Sulu\Component\Content\Template\Exceptions\InvalidXmlException;
use Sulu\Component\Content\Template\Exceptions\InvalidArgumentException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;


class TemplateReader implements LoaderInterface
{
    private $nameKey = "name";
    private $propertiesKey = "properties";
    private $paramsKey = "params";

    private $pathToProperties = "/x:template/x:properties/x:property";
    private $pathToParams = "x:params/x:param";

    private $xmlDocument;

    /**
     * Reades all types from the given path
     * @param $path string path to file with type definitions
     * @param $mandatoryNodes array with key of mandatory node names
     * @throws \Sulu\Component\Content\Template\Exceptions\InvalidXmlException
     * @throws \Sulu\Component\Content\Template\Exceptions\InvalidArgumentException
     * @return array with found definitions of types
     */
    private function readTemplate($path, $mandatoryNodes = array('key', 'view', 'controller', 'cacheLifetime'))
    {

        $template = array();
        $this->xmlDocument = new \DOMDocument();

        try{
            $this->xmlDocument->load($path);

            if (!empty($mandatoryNodes)) {
                $template = $this->getMandatoryNodes($mandatoryNodes);
            }

            /** @var \DOMElement $node */
            /** @var \DOMNodeList $nodes */
            $template[$this->propertiesKey] = array();
            $xpath = new \DOMXPath($this->xmlDocument);
            $xpath->registerNamespace('x', 'http://schemas.sulu.io/template/template');

            $nodes = $xpath->query($this->pathToProperties);

            foreach ($nodes as $node) {
                $attributes = $this->getAllAttributesOfNode($node);
                $name = $attributes[$this->nameKey];
                $params = $this->getChildrenOfNode($node, $this->pathToParams);
                $template[$this->propertiesKey][$name] = array_merge($attributes, $params);
            }
        } catch(InvalidXmlException $ex) {
            throw $ex;
        } catch(Exception $ex) {
            throw new InvalidArgumentException("Path is invalid: " + $path);
        }

        return $template;
    }

    /**
     * Get values of mandatory fields
     * @param $mandatoryNodes
     * @throws InvalidXmlException
     * @return array with mandatoryfields-keys and -values
     */
    private function getMandatoryNodes($mandatoryNodes)
    {
        $mandatoryFields = array();

        /** @var \DOMDocument $xmlDocument */
        foreach ($mandatoryNodes as $node) {
            try {
                $value = $this->xmlDocument->getElementsByTagName($node)->item(0)->nodeValue;
                $mandatoryFields[$node] = $value;
            } catch (Exception $ex) {
                throw new InvalidXmlException("Missing or empty mandatory node in xml!");
            }
        }

        return $mandatoryFields;
    }

    /**
     * Returns attributes form a node
     * @param $node
     * @return array
     */
    private function getAllAttributesOfNode($node)
    {
        $attributes = array();

        /** @var \DOMElement $node */
        if ($node->hasAttributes()) {
            for ($i = 0; $i < $node->attributes->length; $i++) {
                $value = $node->attributes->item($i)->nodeValue;

                if(is_numeric($value)) {
                    $value = $value + 0;
                } else if ($value === "true") {
                    $value = true;
                } else if ($value === "false") {
                    $value = false;
                }

                $attributes[$node->attributes->item($i)->nodeName] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Returns an array with all the attributes from the children of a node
     * @param $node
     * @param $path
     * @return array
     * @internal param $paramsTag
     */
    private function getChildrenOfNode($node, $path) {

        $keyValue = array();

        /** @var \DOMElement $node */
        if($node->hasChildNodes()) {

            $keyValue[$this->paramsKey] = array();
            $xpath = new \DOMXPath($this->xmlDocument);
            $xpath->registerNamespace('x', 'http://schemas.sulu.io/template/template');

            $children = $xpath->query($path,$node);

            foreach($children as $child){
                $keyValue[$this->paramsKey] = array_merge($keyValue[$this->paramsKey], $this->getAttributesAsKeyValuePairs($child));
            }
        }

        return $keyValue;
    }

    /**
     * Returns attributes as key value pairs (e.g. for params)
     * @param $node
     * @return array
     */
    private function getAttributesAsKeyValuePairs($node) {

        $keyValue = array();

        if ($node->hasAttributes()) {
            $keyValue[$node->attributes->item(0)->nodeValue] = $node->attributes->item(1)->nodeValue;
        }

        return $keyValue;

    }

    /**
     * Loads a resource.
     *
     * @param mixed $resource The resource
     * @param string $type     The resource type
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
     * @throws \Gedmo\Exception\FeatureNotImplementedException
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
     * @throws \Gedmo\Exception\FeatureNotImplementedException
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
     * @throws \Gedmo\Exception\FeatureNotImplementedException
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        throw new FeatureNotImplementedException();
    }

}
