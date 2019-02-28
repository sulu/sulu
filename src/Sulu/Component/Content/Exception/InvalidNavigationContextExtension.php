<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class InvalidNavigationContextExtension extends \Exception
{
    /**
     * @var string[]
     */
    private $selectedNavContext;

    /**
     * @var string[]
     */
    private $navContexts;

    /**
     * @param array $selectedNavContext
     * @param array $navContexts
     */
    public function __construct($selectedNavContext, $navContexts)
    {
        parent::__construct(
            sprintf(
                'Navigation Context "%s" not found in [%s]',
                implode(',', $selectedNavContext),
                implode(',', $navContexts)
            )
        );
        $this->selectedNavContext = $selectedNavContext;
        $this->navContexts = $navContexts;
    }

    /**
     * @return string[]
     */
    public function getSelectedNavContext()
    {
        return $this->selectedNavContext;
    }

    /**
     * @return string
     */
    public function getNavContexts()
    {
        return $this->navContexts;
    }
}
