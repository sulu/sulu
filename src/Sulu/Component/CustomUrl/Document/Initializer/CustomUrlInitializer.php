<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document\Initializer;

use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Sulu\Component\CustomUrl\Document\CustomUrlNodeType;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initializes custom-url nodes.
 */
class CustomUrlInitializer implements InitializerInterface
{
    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        NodeManager $nodeManager,
        PathBuilder $pathBuilder,
        WebspaceManagerInterface $webspaceManager,
        SessionManagerInterface $sessionManager
    ) {
        $this->nodeManager = $nodeManager;
        $this->pathBuilder = $pathBuilder;
        $this->webspaceManager = $webspaceManager;
        $this->sessionManager = $sessionManager;
    }

    public function initialize(OutputInterface $output)
    {
        $this->sessionManager->getSession()->getWorkspace()->getNodeTypeManager()->registerNodeType(
            new CustomUrlNodeType(),
            true
        );

        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $itemsPath = $this->pathBuilder->build(
                ['%base%', $webspace->getKey(), '%custom-urls%', '%custom-urls-items%']
            );
            $routesPath = $this->pathBuilder->build(
                ['%base%', $webspace->getKey(), '%custom-urls%', '%custom-urls-routes%']
            );

            $output->writeln(sprintf('<info>Custom-URLS</info>: %s and %s', $itemsPath, $routesPath));

            $this->nodeManager->createPath($itemsPath);
            $this->nodeManager->createPath($routesPath);
            $this->nodeManager->save();
        }
    }
}
