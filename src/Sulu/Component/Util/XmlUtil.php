<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Webmozart\Assert\Assert;

/**
 * Utilties for extracting data from a dom-document using xpath.
 */
class XmlUtil
{
    /**
     * Returns value of path.
     *
     * @param string $path
     * @param \DOMNode $context
     *
     * @return bool|float|int|string|null
     */
    public static function getValueFromXPath($path, \DOMXPath $xpath, \DOMNode $context = null, $default = null)
    {
        $result = $xpath->query($path, $context);
        Assert::object($result, \sprintf('XPath Expression "%s" is invalid.', $path));

        if (0 === $result->length) {
            return $default;
        }

        $item = $result->item(0);
        if (null === $item) {
            return $default;
        }

        return $item->nodeValue;
    }

    /**
     * Returns boolean value of path.
     *
     * @param string $path
     * @param \DOMNode $context
     *
     * @return bool|null
     */
    public static function getBooleanValueFromXPath($path, \DOMXPath $xpath, \DOMNode $context = null, $default = null)
    {
        $value = self::getValueFromXPath($path, $xpath, $context, $default);

        if (null === $value) {
            return null;
        }

        return 'true' === $value || true === $value;
    }
}
