<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Parser;

use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Property;
use Sulu\Bundle\AdminBundle\ResourceMetadata\Schema\Schema;
use Sulu\Component\Content\Metadata\XmlParserTrait;

class SchemaXmlParser
{
    use XmlParserTrait;

    public function load(\DOMXPath $xpath, \DOMNode $contextNode)
    {
        $allOfNode = $xpath->query('x:allOf', $contextNode)->item(0);
        $allOfs = [];
        if ($allOfNode) {
            $allOfs = $this->loadAllOfs($xpath, $allOfNode, $contextNode);
        }

        $anyOfNode = $xpath->query('x:anyOf', $contextNode)->item(0);
        $anyOfs = [];
        if ($anyOfNode) {
            $anyOfs = $this->loadAnyOfs($xpath, $anyOfNode, $contextNode);
        }

        $propertiesNode = $xpath->query('x:properties', $contextNode)->item(0);
        $properties = [];
        if ($propertiesNode) {
            $properties = $this->loadProperties($xpath, $propertiesNode);
        }

        return new Schema($properties, $anyOfs, $allOfs);
    }

    private function loadAllOfs(\DOMXPath $xpath, \DOMNode $contextNode)
    {
        $allOfs = [];
        foreach ($xpath->query('x:schema', $contextNode) as $node) {
            $allOfs[] = $this->load($xpath, $node);
        }

        return $allOfs;
    }

    private function loadAnyOfs(\DOMXPath $xpath, \DOMNode $contextNode)
    {
        $anyOfs = [];
        foreach ($xpath->query('x:schema', $contextNode) as $node) {
            $anyOfs[] = $this->load($xpath, $node);
        }

        return $anyOfs;
    }

    private function loadProperties(\DOMXPath $xpath, \DOMNode $contextNode)
    {
        $properties = [];
        foreach ($xpath->query('x:property', $contextNode) as $node) {
            $properties[] = $this->loadProperty($xpath, $node);
        }

        return $properties;
    }

    private function loadProperty(\DOMXPath $xpath, \DOMNode $contextNode)
    {
        return new Property(
            $this->getValueFromXPath('@name', $xpath, $contextNode),
            $this->getValueFromXPath('@mandatory', $xpath, $contextNode, false),
            $this->getValueFromXPath('@value', $xpath, $contextNode)
        );
    }
}
