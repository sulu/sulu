<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Functional;

use Sulu\Bundle\TestBundle\Testing\KernelTestCase;

abstract class BaseTestCase extends KernelTestCase
{
    public const BASE_NAME = 'test';

    public const BASE_PATH = '/test';

    public function setUp(): void
    {
        $workspace = $this->getSession()->getWorkspace();
        $nodeTypeManager = $workspace->getNodeTypeManager();
        if (!$nodeTypeManager->hasNodeType('mix:test')) {
            $nodeTypeManager->registerNodeTypesCnd(<<<'EOT'
[mix:test] > mix:referenceable mix
[mix:mapping5] > mix:referenceable mix
[mix:mapping10] > mix:referenceable mix
EOT, true
            );
        }

        $namespaceRegistry = $workspace->getNamespaceRegistry();
        $namespaceRegistry->registerNamespace('lsys', 'http://example.com/lsys');
        $namespaceRegistry->registerNamespace('nsys', 'http://example.com/nsys');
        $namespaceRegistry->registerNamespace('lcon', 'http://example.com/lcon');
        $namespaceRegistry->registerNamespace('ncon', 'http://example.com/ncon');
    }

    protected function initPhpcr()
    {
        $session = $this->getSession();

        if ($session->getRootNode()->hasNode(self::BASE_NAME)) {
            $session->removeItem(self::BASE_PATH);
            $session->save();
        }
    }

    protected function getSession()
    {
        return $this->getContainer()->get('doctrine_phpcr.session');
    }

    protected function getDocumentManager()
    {
        return $this->getContainer()->get('sulu_document_manager.document_manager');
    }

    protected function generateDataSet(array $options)
    {
        $options = \array_merge([
            'locales' => ['en'],
        ], $options);

        $manager = $this->getDocumentManager();
        $document = $manager->create('full');

        foreach ($options['locales'] as $locale) {
            $manager->persist($document, $locale, [
                'path' => self::BASE_PATH,
            ]);
        }
    }
}
