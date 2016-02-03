<?php

namespace Sulu\Component\Util;

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
        try {
            $result = $xpath->query($path, $context);
            if ($result->length === 0) {
                return $default;
            }

            $item = $result->item(0);
            if ($item === null) {
                return $default;
            }

            if ('true' === $item->nodeValue) {
                return true;
            }

            if ('false' === $item->nodeValue) {
                return false;
            }

            return $item->nodeValue;
        } catch (\Exception $ex) {
            return $default;
        }
    }
}
