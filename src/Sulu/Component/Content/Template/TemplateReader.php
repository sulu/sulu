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

        $result['properties'] = $this->loadProperties('/x:template/x:properties/x:*', $xpath);

        return $result;
    }

    private function loadProperties($path, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            if ($node->tagName === 'property') {
                $value = $this->loadProperty($xpath, $node);
                $result[$value['name']] = $value;
            }
        }

        return $result;
    }

    private function loadProperty(\DOMXPath $xpath, \DOMNode $node)
    {
        $result = array(
            'name' => $this->getValueFromXPath('@name', $xpath, $node),
            'title' => $this->getValueFromXPath('@title', $xpath, $node),
            'type' => $this->getValueFromXPath('@type', $xpath, $node),
            'mandatory' => $this->getBooleanValueFromXPath('@mandatory', $xpath, $node),
            'minOccurs' => $this->getValueFromXPath('@minOccurs', $xpath, $node),
            'maxOccurs' => $this->getValueFromXPath('@maxOccurs', $xpath, $node),
            'tags' => $this->loadTags('x:tag', $xpath, $node),
            'params' => $this->loadParams('x:params/x:param', $xpath, $node)
        );

        return $result;
    }

    private function loadTags($path, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $result[] = $this->loadTag($xpath, $node);
        }

        return $result;
    }

    private function loadTag(\DOMXPath $xpath, \DOMNode $node)
    {
        $result = array(
            'name' => $this->getValueFromXPath('@name', $xpath, $node),
            'priority' => $this->getValueFromXPath('@priority', $xpath, $node),
        );

        return $result;
    }

    private function loadParams($path, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = array();

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $result[] = $this->loadParam($xpath, $node);
        }

        return $result;
    }

    private function loadParam(\DOMXPath $xpath, \DOMNode $node)
    {
        $result = array(
            'name' => $this->getValueFromXPath('@name', $xpath, $node),
            'value' => $this->getValueFromXPath('@value', $xpath, $node),
        );

        return $result;
    }

    private function getBooleanValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null)
    {
        return $this->getValueFromXPath($path, $xpath, $context) === 'true' ? true : false;
    }

    /**
     * returns value of path
     */
    private function getValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null, $default = null)
    {
        try {
            return $xpath->query($path, $context)->item(0)->nodeValue;
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
