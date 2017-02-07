<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR;

/**
 * cleans path strings.
 */
interface PathCleanupInterface
{
    /**
     * returns a clean string.
     *
     * @param string $dirty        dirty string to cleanup
     * @param string $languageCode
     *
     * @return string clean string
     */
    public function cleanup($dirty, $languageCode = null);

    /**
     * returns TRUE if path is valid.
     *
     * @param string $path
     *
     * @return bool
     */
    public function validate($path);
}
