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
 * Utility methods to handle wildcard urls.
 */
final class WildcardUrlUtil
{
    /**
     * Cannot instantiate this class.
     */
    final private function __construct()
    {
    }

    /**
     * Returns regular expression to match given portal-url.
     *
     * @param string $portalUrl
     *
     * @return string
     */
    private static function getRegularExpression($portalUrl)
    {
        $patternUrl = rtrim($portalUrl, '/');
        $patternUrl = preg_quote($patternUrl);
        $patternUrl = str_replace(['/', '\*'], ['\/', '([^\/.]+)'], $patternUrl);

        return sprintf('/^%s($|([\/].*)|([.].*))$/', $patternUrl);
    }

    /**
     * Matches given url with portal-url.
     *
     * @param string $url
     * @param string $portalUrl
     *
     * @return bool
     */
    public static function match($url, $portalUrl)
    {
        return preg_match(self::getRegularExpression($portalUrl), $url);
    }

    /**
     * Replaces wildcards with occurrences in the given url.
     *
     * @param string $url
     * @param string $portalUrl
     *
     * @return string
     */
    public static function resolve($url, $portalUrl)
    {
        $regexp = self::getRegularExpression($portalUrl);

        if (preg_match($regexp, $url, $matches)) {
            for ($i = 0, $countStar = substr_count($portalUrl, '*'); $i < $countStar; ++$i) {
                $pos = strpos($portalUrl, '*');
                if ($pos !== false) {
                    $portalUrl = substr_replace($portalUrl, $matches[$i + 1], $pos, 1);
                }
            }

            return $portalUrl;
        }

        return;
    }
}
