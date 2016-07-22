<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DocumentManager;

use Doctrine\Common\Persistence\ConnectionRegistry;
use PHPCR\WorkspaceInterface;
use Sulu\Bundle\DocumentManagerBundle\Initializer\InitializerInterface;
use Sulu\Component\PHPCR\NodeTypes\Base\SuluNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\ContentNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\HomeNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\PageNodeType;
use Sulu\Component\PHPCR\NodeTypes\Content\SnippetNodeType;
use Sulu\Component\PHPCR\NodeTypes\Path\PathNodeType;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initialize the namespaces and content types for Sulu Content Documents.
 */
class ContentInitializer implements InitializerInterface
{
    /**
     * @var ConnectionRegistry
     */
    private $connectionRegistry;

    /**
     * @var string
     */
    private $languageNamespace;

    public function __construct(ConnectionRegistry $connectionRegistry, $languageNamespace)
    {
        $this->connectionRegistry = $connectionRegistry;
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(OutputInterface $output, $purge = false)
    {
        foreach ($this->connectionRegistry->getConnections() as $connection) {
            $this->initializeNamespaces($output, $connection->getWorkspace());
            $this->initializeContentTypes($output, $connection->getWorkspace());
        }
    }

    private function initializeNamespaces(OutputInterface $output, WorkspaceInterface $workspace)
    {
        $output->writeln('  <info>content namespaces</>:');
        $namespaceRegistry = $workspace->getNamespaceRegistry();
        $existingPrefixes = $namespaceRegistry->getPrefixes();
        foreach ([
            'sulu' => 'http://sulu.io/phpcr',
            'sec' => 'http://sulu.io/phpcr/sec',
            'settings' => 'http://sulu.io/phpcr/settings',
            $this->languageNamespace => 'http://sulu.io/phpcr/locale',
        ] as $prefix => $uri) {
            if (in_array($prefix, $existingPrefixes)) {
                $output->writeln(sprintf('  [ ] %s:%s', $prefix, $uri));
                continue;
            }

            $output->writeln(sprintf('  [+] %s:%s', $prefix, $uri));

            $namespaceRegistry->registerNamespace($prefix, $uri);
        }
    }

    private function initializeContentTypes(OutputInterface $output, WorkspaceInterface $workspace)
    {
        $output->writeln('  <info>content node types</>:');

        foreach ([
            new SuluNodeType(),
            new PathNodeType(),
            new ContentNodeType(),
            new SnippetNodeType(),
            new PageNodeType(),
            new HomeNodeType(),
        ] as $nodeType) {
            $output->writeln(sprintf('  [*] %s', $nodeType->getName()));
            $workspace->getNodeTypeManager()->registerNodeType($nodeType, true);
        }
    }
}
