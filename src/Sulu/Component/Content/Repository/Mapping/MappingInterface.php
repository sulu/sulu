<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository\Mapping;

/**
 * Interface for mapping definition of content-repository.
 */
interface MappingInterface
{
    /**
     * Indicates if content-repository should follow shadow links.
     *
     * @return bool
     */
    public function hydrateShadow();

    /**
     * Indicates if content-repository should follow internal links.
     *
     * @return bool
     */
    public function followInternalLink();

    /**
     * Indicates if content-repository should follow ghost.
     *
     * @return bool
     */
    public function hydrateGhost();

    /**
     * Returns list of properties.
     *
     * @return string[]
     */
    public function getProperties();
}
