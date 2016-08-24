<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

/**
 * Interface for resource-locator generator.
 */
interface ResourceLocatorGeneratorInterface
{
    /**
     * Generates resource-locator with the given title and parent-path.
     *
     * @param string $title
     * @param string $parentPath
     *
     * @return string
     */
    public function generate($title, $parentPath = null);
}
