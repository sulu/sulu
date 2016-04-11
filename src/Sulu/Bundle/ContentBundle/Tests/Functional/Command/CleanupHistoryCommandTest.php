<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Command;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Command\CleanupHistoryCommand;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CleanupHistoryCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var RlpStrategyInterface
     */
    private $rlpStrategy;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->rlpStrategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $cleanupCommand = new CleanupHistoryCommand();
        $cleanupCommand->setApplication($application);
        $cleanupCommand->setContainer($this->getContainer());
        $this->tester = new CommandTester($cleanupCommand);
    }

    private function createContentNode(NodeInterface $parent, $webspaceKey, $title, $url, $locale)
    {
        $node = $parent->addNode($title);
        $node->setProperty('i18n:' . $locale . '-template', 'default');
        $node->setProperty('i18n:' . $locale . '-creator', 1);
        $node->setProperty('i18n:' . $locale . '-created', new \DateTime());
        $node->setProperty('i18n:' . $locale . '-changer', 1);
        $node->setProperty('i18n:' . $locale . '-changed', new \DateTime());
        $node->setProperty('i18n:' . $locale . '-title', $title);
        $node->setProperty('i18n:' . $locale . '-state', WorkflowStage::PUBLISHED);
        $node->setProperty('i18n:' . $locale . '-published', new \DateTime());
        $node->setProperty('i18n:' . $locale . '-url', $url);
        $node->addMixin('sulu:page');

        return $node;
    }

    private function initNoHistory($webspaceKey, $locale)
    {
        $this->initPhpcr();
        $session = $this->sessionManager->getSession();

        $contentNode = $this->sessionManager->getContentNode($webspaceKey);
        $teamNode = $this->createContentNode($contentNode, $webspaceKey, 'team', '/team', $locale);
        $this->createContentNode($teamNode, $webspaceKey, 'daniel', '/team/daniel', $locale);
        $this->createContentNode($teamNode, $webspaceKey, 'johannes', '/team/johannes', $locale);

        $session->save();

        $teamDocument = $this->documentManager->find($teamNode->getIdentifier(), 'en');
        $teamDocument->setResourceSegment('/team');
        $this->rlpStrategy->save($teamDocument, 1); // Will also create routes for child nodes

        $session->save();
        $session->refresh(false);
    }

    private function initHistory($webspaceKey, $locale)
    {
        $this->initPhpcr();
        $session = $this->sessionManager->getSession();

        $contentNode = $this->sessionManager->getContentNode($webspaceKey);
        $teamNode = $this->createContentNode($contentNode, $webspaceKey, 'team', '/team', $locale);
        $this->createContentNode($teamNode, $webspaceKey, 'daniel', '/team/daniel', $locale);
        $this->createContentNode($teamNode, $webspaceKey, 'johannes', '/team/johannes', $locale);
        $this->createContentNode($contentNode, $webspaceKey, 'about-us', '/about-us', $locale);

        $session->save();

        $teamDocument = $this->documentManager->find($teamNode->getIdentifier());
        $teamDocument->setResourceSegment('/team'); // Will also create routes for child nodes
        $this->rlpStrategy->save($teamDocument, 1);
        $session->save();
        $session->refresh(false);

        $teamDocument->setResourceSegment('/my-test');
        $this->rlpStrategy->save($teamDocument, 1);
        $session->save();
        $session->refresh(false);
    }

    public function dataProviderOnlyRoot()
    {
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->rlpStrategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');

        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return [
            [
                $webspaceKey,
                $locale,
                true,
                null,
                [
                    'contains' => [
                        '/' => false,
                    ],
                    'not-contains' => [],
                ],
            ],
            [
                $webspaceKey,
                $locale,
                false,
                null,
                [
                    'contains' => [
                        '/' => false,
                    ],
                    'not-contains' => [],
                ],
            ],
        ];
    }

    public function dataProviderNoHistory()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return [
            [
                $webspaceKey,
                $locale,
                true,
                null,
                [
                    'contains' => [
                        '/' => false,
                        '/team' => false,
                        '/team/daniel' => false,
                        '/team/johannes' => false,
                    ],
                    'not-contains' => [],
                ],
            ],
            [
                $webspaceKey,
                $locale,
                false,
                null,
                [
                    'contains' => [
                        '/' => false,
                        '/team' => false,
                        '/team/daniel' => false,
                        '/team/johannes' => false,
                    ],
                    'not-contains' => [],
                ],
            ],
        ];
    }

    public function dataProviderHistory()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return [
            [
                $webspaceKey,
                $locale,
                true,
                null,
                [
                    'contains' => [
                        '/' => false,
                        '/my-test' => false,
                        '/my-test/daniel' => false,
                        '/my-test/johannes' => false,
                        '/team' => true,
                        '/team/daniel' => true,
                        '/team/johannes' => true,
                    ],
                    'not-contains' => [],
                ],
            ],
            [
                $webspaceKey,
                $locale,
                false,
                null,
                [
                    'contains' => [
                        '/' => false,
                        '/my-test' => false,
                        '/my-test/daniel' => false,
                        '/my-test/johannes' => false,
                        '/team' => true,
                        '/team/daniel' => true,
                        '/team/johannes' => true,
                    ],
                    'not-contains' => [],
                ],
            ],
            [
                $webspaceKey,
                $locale,
                false,
                '/my-test',
                [
                    'contains' => [
                        '/my-test' => false,
                        '/my-test/daniel' => false,
                        '/my-test/johannes' => false,
                    ],
                    'not-contains' => [
                        '/',
                        '/team',
                        '/team/daniel',
                        '/team/johannes',
                    ],
                ],
            ],
            [
                $webspaceKey,
                $locale,
                false,
                '/team',
                [
                    'contains' => [
                        '/team' => true,
                        '/team/daniel' => true,
                        '/team/johannes' => true,
                    ],
                    'not-contains' => [
                        '/',
                        '/my-test',
                        '/my-test/daniel',
                        '/my-test/johannes',
                    ],
                ],
            ],
        ];
    }

    public function dataProviderException()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return [
            [$webspaceKey, $locale, false, '/team'],
            [$webspaceKey, $locale, true, '/team'],
        ];
    }

    /**
     * @dataProvider dataProviderOnlyRoot
     */
    public function testRunOnlyRoot($webspaceKey, $locale, $dryRun, $basePath, $urls)
    {
        $this->initPhpcr();

        $this->runCommandTest($webspaceKey, $locale, $dryRun, $basePath, $urls);
    }

    /**
     * @dataProvider dataProviderNoHistory
     */
    public function testRunNoHistory($webspaceKey, $locale, $dryRun, $basePath, $urls)
    {
        $this->initNoHistory($webspaceKey, $locale);

        $this->runCommandTest($webspaceKey, $locale, $dryRun, $basePath, $urls);
    }

    /**
     * @dataProvider dataProviderHistory
     */
    public function testRunHistory($webspaceKey, $locale, $dryRun, $basePath, $urls)
    {
        $this->initHistory($webspaceKey, $locale);

        $this->runCommandTest($webspaceKey, $locale, $dryRun, $basePath, $urls);
    }

    /**
     * @dataProvider dataProviderException
     */
    public function testRunException($webspaceKey, $locale, $dryRun, $basePath)
    {
        $this->tester->execute(
            [
                'webspaceKey' => $webspaceKey,
                'locale' => $locale,
                '--dry-run' => $dryRun,
                '--base-path' => $basePath,
            ]
        );
        $output = $this->tester->getDisplay();

        $this->assertEquals(sprintf('Resource-Locator "%s" not found', $basePath), $output);
    }

    private function runCommandTest($webspaceKey, $locale, $dryRun, $basePath, $urls)
    {
        $this->tester->execute(
            [
                'webspaceKey' => $webspaceKey,
                'locale' => $locale,
                '--dry-run' => $dryRun,
                '--base-path' => $basePath,
            ]
        );
        $output = $this->tester->getDisplay();

        $session = $this->sessionManager->getSession();
        $session->refresh(false);

        foreach ($urls['contains'] as $url => $state) {
            $this->outputContains($output, $url, $state);

            if ($dryRun) {
                $this->assertTrue($this->exists($webspaceKey, $locale, $url), $url);
            } else {
                $this->assertEquals($state, !$this->exists($webspaceKey, $locale, $url));
            }
        }

        foreach ($urls['not-contains'] as $url) {
            $this->outputNotContains($output, $url);

            $this->assertTrue($this->exists($webspaceKey, $locale, $url));
        }

        if ($dryRun) {
            $this->outputIsDryRun($output);
        } else {
            $this->outputIsSaving($output);
        }
    }

    private function outputContains($output, $path, $state = true)
    {
        if (!$state) {
            $this->assertContains('Processing aborted: ' . $path, $output);
        } else {
            $this->assertContains('Processing: ' . $path, $output);
        }
    }

    private function outputNotContains($output, $path)
    {
        $this->assertNotContains('Processing aborted: ' . $path . "\n", $output);
        $this->assertNotContains('Processing: ' . $path . "\n", $output);
    }

    private function outputIsDryRun($output)
    {
        $this->assertContains('Dry run complete', $output);
    }

    private function outputIsSaving($output)
    {
        $this->assertContains('Saving ...', $output);
    }

    private function exists($webspace, $locale, $route)
    {
        if ($route === '/') {
            return true;
        }

        $session = $this->sessionManager->getSession();
        $fullPath = sprintf('%s/%s', $this->sessionManager->getRoutePath($webspace, $locale), ltrim($route, '/'));

        return $session->nodeExists($fullPath);
    }
}
