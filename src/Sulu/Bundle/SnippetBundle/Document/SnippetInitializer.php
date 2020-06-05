<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Document;

use Doctrine\Common\Persistence\ConnectionRegistry;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Sulu\Component\DocumentManager\PathBuilder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initializes phpcr-nodes for snippets.
 */
class SnippetInitializer implements InitializerInterface
{
    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    public function __construct(
        ConnectionRegistry $connectionRegistry,
        PathBuilder $pathBuilder
    ) {
        $this->connectionRegistry = $connectionRegistry;
        $this->pathBuilder = $pathBuilder;
    }

    public function initialize(OutputInterface $output, $purge = false)
    {
        foreach ($this->connectionRegistry->getConnections() as $connection) {
            $this->initializeSnippetPath($output, $connection);
        }
    }

    public function initializeSnippetPath(OutputInterface $output, SessionInterface $session)
    {
        $snippetPath = $this->pathBuilder->build(['%base%', '%snippet%']);

        if (true === $session->nodeExists($snippetPath)) {
            $output->writeln(\sprintf('  [ ] <info>snippet path:</info>: %s ', $snippetPath));

            return;
        }

        $output->writeln(\sprintf('  [+] <info>snippet path:</info>: %s ', $snippetPath));

        $currentNode = $session->getRootNode();
        $pathSegments = \explode('/', \trim($snippetPath, '/'));

        foreach ($pathSegments as $pathSegment) {
            if (!$currentNode->hasNode($pathSegment)) {
                $currentNode->addNode($pathSegment);
            }

            $currentNode = $currentNode->getNode($pathSegment);
        }

        $session->save();
    }
}
