<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatLoader;

use DOMElement;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Class XmlFormatLoader.
 */
class XmlFormatLoader extends FileLoader
{
    const SCHEME_PATH = '/schema/formats/formats-1.0.xsd';

    const XML_NAMESPACE_URI = 'http://schemas.sulu.io/media/formats';

    /**
     * @var \DOMXPath
     */
    private $xpath;

    /**
     * @var array
     */
    private $defaultOptions = [];

    /**
     * @return array
     */
    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    /**
     * @param array $defaultOptions
     */
    public function setDefaultOptions($defaultOptions)
    {
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * Load formats from a xml file.
     *
     * @param mixed  $resource The resource
     * @param string $type     The resource type
     *
     * @return array The formats array for the given resource
     */
    public function load($resource, $type = null)
    {
        $path = $this->getLocator()->locate($resource);

        $formats = $this->parseXml($path);

        return $formats;
    }

    /**
     * @param $file
     *
     * @return array
     */
    private function parseXml($file)
    {
        $formats = [];

        // load xml file
        $xmlDoc = XmlUtils::loadFile($file, __DIR__ . static::SCHEME_PATH);

        $this->xpath = new \DOMXPath($xmlDoc);
        $this->xpath->registerNamespace('x', static::XML_NAMESPACE_URI);

        /*
         * @var DOMElement
         */
        foreach ($this->xpath->query('/x:formats/x:format') as $formatNode) {
            $name = $this->xpath->query('x:name', $formatNode)->item(0)->nodeValue;
            if (!isset($formats[$name])) {
                $commands = [];
                foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
                    $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
                    $parameters = [];
                    $parameterNodes = $this->xpath->query('x:parameters/x:parameter', $commandNode);
                    foreach ($parameterNodes as $parameterNode) {
                        $value = $parameterNode->nodeValue;
                        if ($value === 'true') {
                            $value = true;
                        } elseif ($value === 'false') {
                            $value = false;
                        }
                        $parameters[$parameterNode->attributes->getNamedItem('name')->nodeValue] = $value;
                    }

                    $command = [
                        'action' => $action,
                        'parameters' => $parameters,
                    ];
                    $commands[] = $command;
                }

                $options = [];
                $optionNodes = $this->xpath->query('x:options/x:option', $formatNode);
                foreach ($optionNodes as $optionNode) {
                    $options[$optionNode->attributes->getNamedItem('name')->nodeValue] = $optionNode->nodeValue;
                }

                $formats[$name] = [
                    'name' => $name,
                    'commands' => $commands,
                    'options' => array_merge($this->defaultOptions, $options),
                ];
            }
        }

        return $formats;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
