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
use Symfony\Component\Config\Util\XmlUtils;

/**
 * reads a template xml and returns a array representation
 */
class TemplateReader implements LoaderInterface
{
    const SCHEME_PATH = '/Resources/schema/template/template-1.0.xsd';

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        // read file
        $xmlDocument = XmlUtils::loadFile($resource, __DIR__ . static::SCHEME_PATH);

        // generate xpath for file
        $xpath = new \DOMXPath($xmlDocument);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/template/template');

        // init result
        $result = array();

        // root attributes
        $result['key'] = $this->getValueFromXPath('/x:template/x:key', $xpath);
        $result['view'] = $this->getValueFromXPath('/x:template/x:view', $xpath);
        $result['controller'] = $this->getValueFromXPath('/x:template/x:controller', $xpath);
        $result['cacheLifetime'] = $this->getValueFromXPath('/x:template/x:cacheLifetime', $xpath);

        return $result;
    }

    /**
     * returns value of path
     */
    private function getValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null)
    {
        return $xpath->query($path, $context)->item(0)->nodeValue;
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
