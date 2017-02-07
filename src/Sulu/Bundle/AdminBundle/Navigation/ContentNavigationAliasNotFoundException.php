<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * This exception is thrown when a content navigation alias is requested, which does not exist.
 */
class ContentNavigationAliasNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var string[]
     */
    private $availableAliases;

    public function __construct($alias, array $availableAliases)
    {
        $this->alias = $alias;
        $this->availableAliases = $availableAliases;
        parent::__construct(
            sprintf(
                'The content navigation alias "%s" does not exist, registered aliases: "%s"',
                $this->alias,
                implode('", "', $this->availableAliases)
            )
        );
    }

    /**
     * The not existent alias, which has been requested.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns the available aliases in this syste.
     *
     * @return string[]
     */
    public function getAvailableAliases()
    {
        return $this->availableAliases;
    }
}
