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
 *
 * @deprecated
 */
class XmlFormatLoader10 extends BaseXmlFormatLoader
{
    const SCHEMA_URI = 'http://schemas.sulu.io/media/formats-1.0.xsd';

    const SCHEME_PATH = '/schema/formats/formats-1.0.xsd';

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        @trigger_error(
            'XmlFormatLoader10 is deprecated since version 1.4 and will be removed in 2.0. Use XmlFormatLoader11 instead.',
            E_USER_DEPRECATED
        );

        return parent::load($resource, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function getKeyFromFormatNode(\DOMNode $formatNode)
    {
        return $this->xpath->query('x:name', $formatNode)->item(0)->nodeValue;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInternalFlagFromFormatNode(\DOMNode $formatNode)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMetaFromFormatNode(\DOMNode $formatNode)
    {
        return [
            'title' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getScaleFromFormatNode(\DOMNode $formatNode)
    {
        foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
            $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
            if ($action === 'scale' || $action === 'resize') {
                $xNode = $this->xpath->query('x:parameters/x:parameter[@name = "x"]', $commandNode)->item(0);
                $yNode = $this->xpath->query('x:parameters/x:parameter[@name = "y"]', $commandNode)->item(0);
                $modeNode = $this->xpath->query('x:parameters/x:parameter[@name = "mode"]', $commandNode)->item(0);
                $retinaNode = $this->xpath->query('x:parameters/x:parameter[@name = "retina"]', $commandNode)->item(0);
                $forceRatioNode = $this->xpath->query(
                    'x:parameters/x:parameter[@name = "forceRatio"]',
                    $commandNode
                )->item(0);

                $xValue = null;
                $yValue = null;
                $forceRatio = static::SCALE_FORCE_RATIO_DEFAULT;
                $retina = static::SCALE_RETINA_DEFAULT;
                if ($xNode !== null && $xNode->nodeValue !== '') {
                    $xValue = intval($xNode->nodeValue);
                }
                if ($yNode !== null && $yNode->nodeValue !== '') {
                    $yValue = intval($yNode->nodeValue);
                }
                if ($xValue === null && $yValue === null) {
                    throw new MissingScaleDimensionException();
                }

                if ($forceRatioNode !== null && $forceRatioNode->nodeValue === 'false') {
                    $forceRatio = false;
                }
                if ($retinaNode !== null && $retinaNode->nodeValue === 'true') {
                    $retina = true;
                }

                return [
                    'x' => $xValue,
                    'y' => $yValue,
                    'mode' => ($modeNode !== null) ? $modeNode->nodeValue : static::SCALE_MODE_DEFAULT,
                    'retina' => $retina,
                    'forceRatio' => $forceRatio,
                ];
            }
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTransformationsFromFormatNode(\DOMNode $formatNode)
    {
        $transformations = [];

        foreach ($this->xpath->query('x:commands/x:command', $formatNode) as $commandNode) {
            $action = $this->xpath->query('x:action', $commandNode)->item(0)->nodeValue;
            if ($action === 'scale' || $action === 'resize') {
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
