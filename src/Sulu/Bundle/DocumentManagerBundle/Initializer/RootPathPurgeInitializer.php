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
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Purges the root path if the purge flag has been set.
 */
class RootPathPurgeInitializer implements InitializerInterface
{
    /**
     * @param string $rootRole
     */
    public function __construct(
        private ConnectionRegistry $connections,
        private PathSegmentRegistry $pathSegments,
        private $rootRole = 'root',
    ) {
    }

    public function initialize(OutputInterface $output, $purge = false)
    {
        if (!$purge) {
            $output->writeln('  [ ] Purging workspaces');

            return;
        }

        /** @var array<SessionInterface> $sessions */
        $sessions = $this->connections->getConnections();
        $rootPath = '/' . $this->pathSegments->getPathSegment($this->rootRole);

        foreach ($sessions as $session) {
            try {
                $session->getRootNode();
            } catch (RepositoryException $e) {
                // TODO: We should catch the more explicit
                // WorkspaceNotFoundException but Jackalope doctrine-dbal does
                // not throw this: https://github.com/jackalope/jackalope-doctrine-dbal/issues/322
                continue;
            }

            if ($session->nodeExists($rootPath)) {
                $session->getNode($rootPath)->remove();
                $session->save();
            }
        }

        $output->writeln('  [-] Purging workspaces');
    }
}
