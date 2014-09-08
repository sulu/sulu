<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

/**
 * Represents the navigation defined in webspace xml
 */
class Navigation
{
    /**
     * @var NavigationContext[]
     */
    private $contexts = array();

    function __construct($contexts = array())
    {
        $this->contexts = $contexts;
    }

    /**
     * @param NavigationContext $context
     */
    public function addContext(NavigationContext $context)
    {
        $this->contexts[] = $context;
    }

    /**
     * @return NavigationContext[]
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * @param NavigationContext[] $contexts
     */
    public function setContexts($contexts)
    {
        $this->contexts = $contexts;
    }
}
