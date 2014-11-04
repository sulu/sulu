<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatLoader;

use DOMElement;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * Class XmlFormatLoader
 * @package Sulu\Bundle\MediaBundle\Media\FormatLoader
 */
class XmlFormatLoader extends FileLoader
{
    const SCHEME_PATH = '/schema/formats/formats-1.0.xsd';

    const XML_NAMESPACE_URI = 'http://schemas.sulu.io/media/formats';

    /**
     * @var  \DOMXPath
     */
    private $xpath;

    /**
     * Load formats from a xml file
     *
     * @param mixed $resource The resource
     * @param string $type The resource type
     * @return array The formats array for the given resource
     */
    public function load($resource, $type = null)
    {
        $path = $this->getLocator()->locate($resource);

        $formats = $this->parseXml($path);

        return $formats;
    }

    /**
     *
     * @param $file
     * @return Portal
     */
    private function parseXml($file)
    {
        $formats = array();

        // load xml file
        $xmlDoc = XmlUtils::loadFile($file, __DIR__ . static::SCHEME_PATH);

        $this->xpath = new \DOMXPath($xmlDoc);
        $this->xpath->registerNamespace('x', static::XML_NAMESPACE_URI);

        /**
         * @var DOMElement $formatNode
         */
        foreach ($this->xpath->query('/x:formats/x:format') as $formatNode) {
            $name = $this->xpath->query('x:name', $formatNode)->item(0)->nodeValue;
            if (!isset($formats[$name])) {
                $commands = array();
                foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
                    $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
                    $parameters = array();
                    $parameterNodes = $this->xpath->query('x:parameters/x:parameter', $commandNode);
                    foreach ($parameterNodes as $parameterNode) {
                        $parameters[$parameterNode->attributes->getNamedItem('name')->nodeValue] = $parameterNode->nodeValue;
                    }

                    $command = array(
                        'action' => $action,
                        'parameters' => $parameters
                    );
                    $commands[] = $command;
                }

                $formats[$name] = array(
                    'name' => $name,
                    'commands' => $commands
                );
            }
        }

        return $formats;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type The resource type
     *
     * @return bool    true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
