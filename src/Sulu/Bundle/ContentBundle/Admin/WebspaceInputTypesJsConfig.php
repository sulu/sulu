<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Injects the input-config for resource-locator per webspace.
 */
class WebspaceInputTypesJsConfig implements JsConfigInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @param WebspaceManagerInterface $webspaceManager
     * @param ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool
     */
    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->resourceLocatorStrategyPool = $resourceLocatorStrategyPool;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        $result = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $result[$webspace->getKey()] = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(
                $webspace->getKey()
            )->getInputType();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_content.webspace_input_types';
    }
}
