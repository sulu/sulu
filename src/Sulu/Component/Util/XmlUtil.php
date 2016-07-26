<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

/**
 * Utilties for extracting data from a dom-document using xpath.
 */
class XmlUtil
{
    /**
     * Returns value of path.
     *
     * @param string $path
     * @param \DOMXPath $xpath
     * @param \DomNode $context
     * @param mixed $default
     *
     * @return bool|null|string|mixed
     */
    public static function getValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null, $default = null)
    {
        $result = $xpath->query($path, $context);
        if ($result->length === 0) {
            return $default;
        }

        $item = $result->item(0);
        if ($item === null) {
            return $default;
        }

        return $item->nodeValue;
    }

    /**
     * Returns boolean value of path.
     *
     * @param string $path
     * @param \DOMXPath $xpath
     * @param \DomNode $context
     * @param mixed $default
     *
     * @return bool|null|string|mixed
     */
    public static function getBooleanValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null, $default = null)
    {
        $value = self::getValueFromXPath($path, $xpath, $context, $default);

        if ($value === null) {
            return;
        }

        return 'true' === $value || true === $value;
    }
}
