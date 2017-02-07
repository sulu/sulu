<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\Compat\StructureInterface;

/**
 * Resolves the structure to an array.
 */
interface StructureResolverInterface
{
    /**
     * This method receives a structure, and should return an array for the template.
     *
     * @param StructureInterface $structure The structure to resolve
     *
     * @return array
     */
    public function resolve(StructureInterface $structure);
}
