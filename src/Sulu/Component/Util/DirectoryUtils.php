<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

/**
 * Utility functions for directories
 *
 * @package Sulu\Component\Util
 */
class DirectoryUtils
{

    /**
     * Copy a directory structure + files to another destination
     *
     * @param $source string Source directory
     * @param $dest string destination directory (will be created if not exist)
     * @return bool everything is OK
     */
    public static function copyRecursive($source, $dest)
    {
        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            if ($dest !== $source . '/' . $entry) {
                self::copyRecursive($source . '/' . $entry, $dest . '/' . $entry);
            }
        }

        // Clean up
        $dir->close();

        return true;
    }

}
