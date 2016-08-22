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

use Sulu\Bundle\MediaBundle\Media\FormatLoader\Exception\MissingScaleDimensionException;

/**
 * Class XmlFormatLoader for the version 1.1 of the image-formats.
 */
class XmlFormatLoader11 extends BaseXmlFormatLoader
{
    const SCHEMA_URI = 'http://schemas.sulu.io/media/formats-1.1.xsd';

    const SCHEME_PATH = '/schema/formats/formats-1.1.xsd';

    /**
     * For a given format node returns the key of the format.
     *
     * @param \DOMNode $formatNode
     *
     * @return string
     */
    protected function getKeyFromFormatNode(\DOMNode $formatNode)
    {
        return $this->xpath->query('@key', $formatNode)->item(0)->nodeValue;
    }

    /**
     * For a given format node returns the meta information of the format.
     *
     * @param \DOMNode $formatNode
     *
     * @return array
     */
    protected function getMetaFromFormatNode(\DOMNode $formatNode)
    {
        $meta = [
            'title' => [],
        ];

        foreach ($this->xpath->query('x:meta/x:title', $formatNode) as $formatTitleNode) {
            $langauge = $this->xpath->query('@lang', $formatTitleNode)->item(0)->nodeValue;
            $meta['title'][$langauge] = $formatTitleNode->nodeValue;
        }

        return $meta;
    }

    /**
     * For a given format node returns the scale information of the format.
     *
     * @param \DOMNode $formatNode
     *
     * @return array
     */
    protected function getScaleFromFormatNode(\DOMNode $formatNode)
    {
        $scale = null;

        $formatScaleNode = $this->xpath->query('x:scale', $formatNode)->item(0);
        if ($formatScaleNode !== null) {
            $xNode = $this->xpath->query('@x', $formatScaleNode)->item(0);
            $yNode = $this->xpath->query('@y', $formatScaleNode)->item(0);
            $modeNode = $this->xpath->query('@mode', $formatScaleNode)->item(0);
            if ($xNode === null && $yNode === null) {
                throw new MissingScaleDimensionException();
            }

            $scale = [
                'x' => ($xNode !== null) ? $xNode->nodeValue : null,
                'y' => ($yNode !== null) ? $yNode->nodeValue : null,
                'mode' => ($modeNode !== null) ? $modeNode->nodeValue : self::SCALE_MODE_DEFAULT,
            ];
        }

        return $scale;
    }

    /**
     * For a given format node returns the transformations for it.
     *
     * @param \DOMNode $formatNode
     *
     * @return array
     */
    protected function getTransformationsFromFormatNode(\DOMNode $formatNode)
    {
        $transformations = [];

        foreach ($this->xpath->query('x:transformations/x:transformation', $formatNode) as $transformationNode) {
            $effectNode = $this->xpath->query('x:effect', $transformationNode)->item(0);
            $parametersNode = $this->xpath->query('x:parameters', $transformationNode)->item(0);
            $transformations[] = [
                'effect' => $effectNode->nodeValue,
                'parameters' => $this->getParametersFromNode($parametersNode),
            ];
        }

        return $transformations;
    }
}
