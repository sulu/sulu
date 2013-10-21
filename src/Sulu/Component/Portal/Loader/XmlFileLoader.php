<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal\Loader;

use Sulu\Component\Portal\Portal;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Util\XmlUtils;

class XmlFileLoader extends FileLoader
{
    /**
     * Loads a portal from a xml file
     *
     * @param mixed $resource The resource
     * @param string $type     The resource type
     * @return Portal The portal object for the given resource
     */
    public function load($resource, $type = null)
    {
        $path = $this->getLocator()->locate($resource);

        // load data in path
        $portal = $this->parseXml($path);

        return $portal;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xml' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     *
     * @param $file
     * @return Portal
     */
    private function parseXml($file)
    {
        $xml = XmlUtils::loadFile($file);

        $portal = new Portal();
        $portal->setName($xml->getElementsByTagName('name')->item(0)->nodeValue);
        $portal->setKey($xml->getElementsByTagName('key')->item(0)->nodeValue);

        return $portal;
    }
}
