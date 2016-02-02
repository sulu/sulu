<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

/**
 * Represents the navigation defined in webspace xml.
 */
class Navigation
{
    /**
     * @var NavigationContext[]
     */
    private $contexts = [];

    /**
     * @var string[]
     */
    private $keys = [];

    public function __construct($contexts = [])
    {
        foreach ($contexts as $context) {
            $this->addContext($context);
        }
    }

    /**
     * @param NavigationContext $context
     */
    public function addContext(NavigationContext $context)
    {
        $this->contexts[] = $context;
        $this->keys[] = $context->getKey();
    }

    /**
     * @return NavigationContext[]
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * @return string[]
     */
    public function getContextKeys()
    {
        return $this->keys;
    }

    /**
     * @param NavigationContext[] $contexts
     */
    public function setContexts($contexts)
    {
        $this->contexts = $contexts;
    }
}
