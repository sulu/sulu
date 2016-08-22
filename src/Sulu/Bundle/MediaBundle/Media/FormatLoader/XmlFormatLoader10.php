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
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Class XmlFormatLoader for the version 1.1 of the image-formats.
 *
 * @deprecated
 */
class XmlFormatLoader10 extends BaseXmlFormatLoader
{
    const SCHEMA_URI = 'http://schemas.sulu.io/media/formats-1.0.xsd';

    const SCHEME_PATH = '/schema/formats/formats-1.0.xsd';

    public function __construct(FileLocatorInterface $locator)
    {
        parent::__construct($locator);
        @trigger_error(
            'XmlFormatLoader10 is deprecated since version 1.4 and will be removed in 2.0. Use XmlFormatLoader11 instead.',
            E_USER_DEPRECATED
        );
    }

    /**
     * For a given format node returns the key of the format.
     *
     * @param \DOMNode $formatNode
     *
     * @return string
     */
    protected function getKeyFromFormatNode(\DOMNode $formatNode)
    {
        return $this->xpath->query('x:name', $formatNode)->item(0)->nodeValue;
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
        return [
            'title' => [],
        ];
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
        foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
            $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
            if ($action === 'scale' || $action === 'resize') {
                $xNode = $this->xpath->query('x:parameters/x:parameter[@name = "x"]', $commandNode)->item(0);
                $yNode = $this->xpath->query('x:parameters/x:parameter[@name = "y"]', $commandNode)->item(0);
                $modeNode = $this->xpath->query('x:parameters/x:parameter[@name = "mode"]', $commandNode)->item(0);

                $xValue = null;
                $yValue = null;
                if ($xNode !== null && $xNode->nodeValue !== '') {
                    $xValue = $xNode->nodeValue;
                }
                if ($yNode !== null && $yNode->nodeValue !== '') {
                    $yValue = $yNode->nodeValue;
                }

                if ($xValue === null && $yValue === null) {
                    throw new MissingScaleDimensionException();
                }

                return [
                    'x' => $xValue,
                    'y' => $yValue,
                    'mode' => ($modeNode !== null) ? $modeNode->nodeValue : self::SCALE_MODE_DEFAULT,
                ];
            }
        }

        return;
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
        // The first "scale" or "resize" transformation gets skipped, because it is already contained
        // in the scale-section of the format (see "getScaleFromFormatNode")
        $scaleCommandSkipped = false;

        foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
            $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
            if ($scaleCommandSkipped === false && ($action === 'scale' || $action === 'resize')) {
                $scaleCommandSkipped = true;
                continue;
            }

            $actionNode = $this->xpath->query('x:action', $commandNode)->item(0);
            $parametersNode = $this->xpath->query('x:parameters', $commandNode)->item(0);

            $transformations[] = [
                'effect' => $actionNode->nodeValue,
                'parameters' => $this->getParametersFromNode($parametersNode),
            ];
        }

        return $transformations;
    }
}
