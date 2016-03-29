<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Initializer;

use Doctrine\Common\Persistence\ConnectionRegistry;
use PHPCR\RepositoryException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates the PHPCR workspaces required by the configuration.
 */
class WorkspaceInitializer implements InitializerInterface
{
    /**
     * @var ConnectionRegistry
     */
    private $registry;

    public function __construct(ConnectionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(OutputInterface $output)
    {
        foreach ($this->registry->getConnections() as $connection) {
            $workspace = $connection->getWorkspace();

            try {
                $connection->getRootNode();
                $output->writeln(sprintf('  [ ] <info>workspace</info>: "%s"', $workspace->getName()));
            } catch (RepositoryException $e) {
                // TODO: We should catch the more explicit
                // WorkspaceNotFoundException but Jackalope doctrine-dbal does
                // not throw this: https://github.com/jackalope/jackalope-doctrine-dbal/issues/322
                $workspace->createWorkspace($workspace->getName());
                $output->writeln(sprintf('  [+] <info>workspace</info>: "%s"', $workspace->getName()));
            }
        }
    }
}
