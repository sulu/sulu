<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Document;

use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathBuilder;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initializes phpcr-nodes for snippets.
 */
class SnippetInitializer implements InitializerInterface
{
    private $nodeManager;
    private $pathBuilder;

    public function __construct(
        NodeManager $nodeManager,
        PathBuilder $pathBuilder
    ) {
        $this->nodeManager = $nodeManager;
        $this->pathBuilder = $pathBuilder;
    }

    public function initialize(OutputInterface $output, $purge = false)
    {
        $snippetPath = $this->pathBuilder->build(['%base%', '%snippet%']);

        if (true === $this->nodeManager->has($snippetPath)) {
            $output->writeln(sprintf('  [ ] <info>snippet path:</info>: %s ', $snippetPath));

            return;
        }

        $output->writeln(sprintf('  [+] <info>snippet path:</info>: %s ', $snippetPath));

        $this->nodeManager->createPath($snippetPath);
        $this->nodeManager->save();
    }
}
