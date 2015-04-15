<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * This exception is thrown when a content navigation alias is requested, which does not exist
 */
class ContentNavigationAliasNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $alias;

    public function __construct($alias)
    {
        $this->alias = $alias;
        parent::__construct(sprintf('The content navigation alias "%s" does not exist!', $this->alias));
    }

    /**
     * The not existent alias, which has been requested
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }
}
