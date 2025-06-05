<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ConstMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class SchemaXmlParser
{
    use XmlParserTrait;

    public function load(\DOMXPath $xpath, \DOMNode $contextNode): SchemaMetadata
    {
        $allOfNode = $xpath->query('x:allOf', $contextNode)->item(0);
        $allOfs = [];
        if ($allOfNode) {
            $allOfs = $this->loadAllOfs($xpath, $allOfNode);
        }

        $anyOfNode = $xpath->query('x:anyOf', $contextNode)->item(0);
        $anyOfs = [];
        if ($anyOfNode) {
            $anyOfs = $this->loadAnyOfs($xpath, $anyOfNode);
        }

        $propertiesNode = $xpath->query('x:properties', $contextNode)->item(0);
        $properties = [];
        if ($propertiesNode) {
            $properties = $this->loadProperties($xpath, $propertiesNode);
        }

        return new SchemaMetadata($properties, $anyOfs, $allOfs);
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
        $value = $this->getValueFromXPath('@value', $xpath, $contextNode);

        if (null !== $value) {
            return new PropertyMetadata(
                $this->getValueFromXPath('@name', $xpath, $contextNode),
                $this->getValueFromXPath('@mandatory', $xpath, $contextNode, false),
                new ConstMetadata($value)
            );
        }

        return new PropertyMetadata(
            $this->getValueFromXPath('@name', $xpath, $contextNode),
            $this->getValueFromXPath('@mandatory', $xpath, $contextNode, false)
        );
    }
}
