<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
trait XmlParserTrait
{
    private function getValueFromXPath($path, \DOMXPath $xpath, ?\DOMNode $context = null, $default = null)
    {
        try {
            $result = $xpath->query($path, $context);
            if (0 === $result->length) {
                return $default;
            }

            $item = $result->item(0);
            if (null === $item) {
                return $default;
            }

            if ('true' === $item->nodeValue) {
                return true;
            }

            if ('false' === $item->nodeValue) {
                return false;
            }

            $numericNodeValue = \intval($item->nodeValue);
            if ((string) $numericNodeValue === $item->nodeValue) {
                return $numericNodeValue;
            }

            return $item->nodeValue;
        } catch (\Exception $ex) {
            return $default;
        }
    }
}
