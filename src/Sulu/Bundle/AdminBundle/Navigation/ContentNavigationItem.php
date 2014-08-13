<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

class ContentNavigationItem
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string[]
     */
    private $groups;

    /**
     * @var string
     */
    private $component;

    /**
     * @var string[]
     */
    private $options;

    /**
     * @var string[]
     */
    private $display;
}
