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
 * Utilties for text manipulation.
 */
class TextUtils
{
    /**
     * UTF-8 safe text truncation.
     *
     * @param string $text   - Text to truncate
     * @param int    $length - Length to truncate to
     * @param string $suffix - This string will replace the last characters of the text
     *
     * @return string
     */
    public static function truncate($text, $length, $suffix = '...')
    {
        $strlen = mb_strlen($text, 'UTF-8');

        if ($strlen > $length) {
            $truncatedLength = $length - strlen($suffix);
            $text = mb_substr($text, 0, $truncatedLength, 'UTF-8') . $suffix;
        }

        return $text;
    }
}
