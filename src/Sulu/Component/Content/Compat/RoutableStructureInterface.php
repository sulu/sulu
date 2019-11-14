<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

interface RoutableStructureInterface
{
    /**
     * twig template of template definition.
     *
     * @return string
     */
    public function getView();

    /**
     * controller which renders the template definition.
     *
     * @return string
     */
    public function getController();

    /**
     * cacheLifeTime of template definition.
     *
     * @return array
     */
    public function getCacheLifeTime();
}
