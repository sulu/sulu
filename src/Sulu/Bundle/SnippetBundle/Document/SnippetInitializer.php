<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Document;

use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Symfony\Component\Console\Output\OutputInterface;

class SnippetInitializer
{
    private $nodeManager;
    private $pathSegmentRegistry;

    public function __construct(
        NodeManager $nodeManager,
        PathSegmentRegistry $pathSegmentRegistry
    )
    {
        $this->nodeManager = $nodeManager;
        $this->pathSegmentRegistry = $pathSegmentRegistry;
    }

    public function initialize(OutputInterface $output)
    {
        $snippetPath = '/' . $this->pathSegmentRegistry->getPathSegment('base') . '/' . $this->pathSegmentRegistry->getPathSegment('snippet');
        $output->writeln(sprintf('<info>Snippets</info>: %s ', $snippetPath));

        if (true === $this->nodeManager->has($snippetPath)) {
            return;
        }

        $this->nodeManager->createPath($snippetPath);
        $this->nodeManager->save();
    }
}
