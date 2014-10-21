<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

use Sulu\Component\Content\StructureInterface;

/**
 * Interface for pages
 */
interface PageInterface extends StructureInterface
{
    /**
     * Returns uuid of parent page
     *
     * @return string
     */
    public function getParentUuid();

    /**
     * Sets uuid of parent page
     *
     * @param string $parentUuid
     */
    public function setParentUuid($parentUuid);
} 
