<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Manages rlp-strategies.
 */
class StrategyManager implements StrategyManagerInterface
{
    /**
     * @var RlpStrategyInterface[]
     */
    private $strategies;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @param RlpStrategyInterface[] $strategies
     * @param WebspaceManagerInterface $webspaceManager
     */
    public function __construct(array $strategies, WebspaceManagerInterface $webspaceManager)
    {
        $this->strategies = $strategies;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategy($name)
    {
        if (!array_key_exists($name, $this->strategies)) {
            throw new StrategyNotExistsException($name, array_keys($this->strategies));
        }

        return $this->strategies[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategyByWebspaceKey($webspaceKey)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        return $this->getStrategy($webspace->getResourceLocatorStrategy());
    }
}
