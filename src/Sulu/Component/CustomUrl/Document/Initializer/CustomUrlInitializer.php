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
use Sulu\Component\CustomUrl\Document\CustomUrlRouteNodeType;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
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

    public function initialize(OutputInterface $output, $purge = false)
    {
        $nodeTypeManager = $this->sessionManager->getSession()->getWorkspace()->getNodeTypeManager();

        foreach ([new CustomUrlNodeType(), new CustomUrlRouteNodeType()] as $nodeType) {
            $nodeTypeManager->registerNodeType($nodeType, true);
        }

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $itemsPath = $this->pathBuilder->build(
                ['%base%', $webspace->getKey(), '%custom_urls%', '%custom_urls_items%']
            );
            $routesPath = $this->pathBuilder->build(
                ['%base%', $webspace->getKey(), '%custom_urls%', '%custom_urls_routes%']
            );

            $output->writeln(sprintf('  <info>%s</info>:', $webspace->getName()));

            if (true === $this->nodeManager->has($itemsPath)) {
                $output->writeln(sprintf('  [ ] <info>items path:</info>: %s ', $itemsPath));
            } else {
                $output->writeln(sprintf('  [+] <info>items path:</info>: %s ', $itemsPath));
                $this->nodeManager->createPath($itemsPath);
            }

            if (true === $this->nodeManager->has($routesPath)) {
                $output->writeln(sprintf('  [ ] <info>items path:</info>: %s ', $routesPath));
            } else {
                $output->writeln(sprintf('  [+] <info>items path:</info>: %s ', $routesPath));
                $this->nodeManager->createPath($routesPath);
            }

            $this->nodeManager->save();
        }
    }
}
