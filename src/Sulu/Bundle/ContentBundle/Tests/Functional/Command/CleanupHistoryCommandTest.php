<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Command;

use Sulu\Bundle\ContentBundle\Command\CleanupHistoryCommand;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
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
    private $phpcrStrategy;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->phpcrStrategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');

        $this->initPhpcr();

        $cleanupCommand = new CleanupHistoryCommand();
        $cleanupCommand->setApplication($application);
        $cleanupCommand->setContainer($this->getContainer());
        $this->tester = new CommandTester($cleanupCommand);
    }

    private function initNoHistory($webspaceKey, $locale)
    {
        $contentNode = $this->sessionManager->getContentNode($webspaceKey);
        $this->phpcrStrategy->save($contentNode, '/team', 1, $webspaceKey, $locale);
        $this->phpcrStrategy->save($contentNode, '/team/daniel', 1, $webspaceKey, $locale);
        $this->phpcrStrategy->save($contentNode, '/team/johannes', 1, $webspaceKey, $locale);
    }

    private function initHistory($webspaceKey, $locale)
    {
        $session = $this->getContainer()->get('doctrine_phpcr')->getManager()->getPhpcrSession();

        $contentNode = $this->sessionManager->getContentNode($webspaceKey);
        $this->phpcrStrategy->save($contentNode, '/team', 1, $webspaceKey, $locale);
        $this->phpcrStrategy->save($contentNode, '/team/daniel', 1, $webspaceKey, $locale);
        $this->phpcrStrategy->save($contentNode, '/team/johannes', 1, $webspaceKey, $locale);
        $this->phpcrStrategy->save($contentNode, '/about-us', 1, $webspaceKey, $locale);

        $this->phpcrStrategy->move('/team', '/my-test', $contentNode, 1, $webspaceKey, $locale);

        $session->save();
        $session->refresh(false);
    }

    public function dataProviderOnlyRoot()
    {
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->phpcrStrategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');

        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return array(
            array(
                $webspaceKey,
                $locale,
                true,
                null,
                array(
                    'contains' => array(
                        '/' => false
                    ),
                    'not-contains' => array()
                ),
            ),
            array(
                $webspaceKey,
                $locale,
                false,
                null,
                array(
                    'contains' => array(
                        '/' => false
                    ),
                    'not-contains' => array()
                ),
            ),
        );
    }

    public function dataProviderNoHistory()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return array(
            array(
                $webspaceKey,
                $locale,
                true,
                null,
                array(
                    'contains' => array(
                        '/' => false,
                        '/team' => false,
                        '/team/daniel' => false,
                        '/team/johannes' => false
                    ),
                    'not-contains' => array()
                ),
            ),
            array(
                $webspaceKey,
                $locale,
                false,
                null,
                array(
                    'contains' => array(
                        '/' => false,
                        '/team' => false,
                        '/team/daniel' => false,
                        '/team/johannes' => false
                    ),
                    'not-contains' => array()
                ),
            ),
        );
    }

    public function dataProviderHistory()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return array(
            array(
                $webspaceKey,
                $locale,
                true,
                null,
                array(
                    'contains' => array(
                        '/' => false,
                        '/my-test' => false,
                        '/my-test/daniel' => false,
                        '/my-test/johannes' => false,
                        '/team' => true,
                        '/team/daniel' => true,
                        '/team/johannes' => true
                    ),
                    'not-contains' => array(),
                ),
            ),
            array(
                $webspaceKey,
                $locale,
                false,
                null,
                array(
                    'contains' => array(
                        '/' => false,
                        '/my-test' => false,
                        '/my-test/daniel' => false,
                        '/my-test/johannes' => false,
                        '/team' => true,
                        '/team/daniel' => true,
                        '/team/johannes' => true
                    ),
                    'not-contains' => array(),
                ),
            ),
            array(
                $webspaceKey,
                $locale,
                false,
                '/my-test',
                array(
                    'contains' => array(
                        '/my-test' => false,
                        '/my-test/daniel' => false,
                        '/my-test/johannes' => false,
                    ),
                    'not-contains' => array(
                        '/',
                        '/team',
                        '/team/daniel',
                        '/team/johannes',
                    ),
                ),
            ),
            array(
                $webspaceKey,
                $locale,
                false,
                '/team',
                array(
                    'contains' => array(
                        '/team' => true,
                        '/team/daniel' => true,
                        '/team/johannes' => true
                    ),
                    'not-contains' => array(
                        '/',
                        '/my-test',
                        '/my-test/daniel',
                        '/my-test/johannes',
                    ),
                ),
            ),
        );
    }

    public function dataProviderException()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return array(
            array($webspaceKey, $locale, false, '/team',),
            array($webspaceKey, $locale, true, '/team',),
        );
    }

    /**
     * @dataProvider dataProviderOnlyRoot
     */
    public function testRunOnlyRoot($webspaceKey, $locale, $dryRun, $basePath, $urls)
    {
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
            array(
                'webspaceKey' => $webspaceKey,
                'locale' => $locale,
                '--dry-run' => $dryRun,
                '--base-path' => $basePath
            )
        );
        $output = $this->tester->getDisplay();

        $this->assertEquals(sprintf('Resource-Locator "%s" not found', $basePath), $output);
    }

    private function runCommandTest($webspaceKey, $locale, $dryRun, $basePath, $urls)
    {
        $this->tester->execute(
            array(
                'webspaceKey' => $webspaceKey,
                'locale' => $locale,
                '--dry-run' => $dryRun,
                '--base-path' => $basePath
            )
        );
        $output = $this->tester->getDisplay();

        foreach ($urls['contains'] as $url => $state) {
            $this->outputContains($output, $url, $state);
        }

        foreach ($urls['not-contains'] as $url) {
            $this->outputNotContains($output, $url);
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
}
