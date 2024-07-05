<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

use Doctrine\Persistence\ConnectionRegistry;
use PHPCR\RepositoryException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates the PHPCR workspaces required by the configuration.
 */
class WorkspaceInitializer implements InitializerInterface
{
    public function __construct(
        private ConnectionRegistry $registry)
    {
    }

    public function initialize(OutputInterface $output, $purge = false)
    {
        foreach ($this->registry->getConnections() as $connection) {
            $workspace = $connection->getWorkspace();

            try {
                $workspace->createWorkspace($workspace->getName());
                $output->writeln(\sprintf('  [+] <info>workspace</info>: "%s"', $workspace->getName()));
            } catch (RepositoryException $e) {
                $output->writeln(\sprintf('  [ ] <info>workspace</info>: "%s"', $workspace->getName()));
            }
        }
    }
}
