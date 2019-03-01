<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * The implementing document can have navigation contexts applied to it.
 *
 * Navigation contexts indicate which navigation trees the implementing
 * document will appear in.
 */
interface NavigationContextBehavior
{
    /**
     * Return the navigation contexts.
     *
     * @return array Array of strings
     */
    public function getNavigationContexts();

    /**
     * Set the navigation contexts. The array should contain a list of keys.
     * (eg. to add it to the default main menu: ['main']).
     *
     * @param array $navigationContexts
     */
    public function setNavigationContexts(array $navigationContexts = []);
}
