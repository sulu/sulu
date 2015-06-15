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

    public function dataProviderOnlyRoot()
    {
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->phpcrStrategy = $this->getContainer()->get('sulu.content.rlp.strategy.tree');

        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return array(
            array($webspaceKey, $locale, true, array('/' => false)),
            array($webspaceKey, $locale, false, array('/' => false)),
        );
    }

    /**
     * @dataProvider dataProviderOnlyRoot
     */
    public function testRunOnlyRoot($webspaceKey, $locale, $dryRun, $urls)
    {
        $this->runCommandTest($webspaceKey, $locale, $dryRun, $urls);
    }

    private function initNoHistory($webspaceKey, $locale)
    {
        $contentNode = $this->sessionManager->getContentNode($webspaceKey);
        $this->phpcrStrategy->save($contentNode, '/team', 1, $webspaceKey, $locale);
        $this->phpcrStrategy->save($contentNode, '/team/daniel', 1, $webspaceKey, $locale);
        $this->phpcrStrategy->save($contentNode, '/team/johannes', 1, $webspaceKey, $locale);
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
                array('/' => false, '/team' => false, '/team/daniel' => false, '/team/johannes' => false)
            ),
            array(
                $webspaceKey,
                $locale,
                false,
                array('/' => false, '/team' => false, '/team/daniel' => false, '/team/johannes' => false)
            ),
        );
    }

    /**
     * @dataProvider dataProviderNoHistory
     */
    public function testRunNoHistory($webspaceKey, $locale, $dryRun, $urls)
    {
        $this->initNoHistory($webspaceKey, $locale);

        $this->runCommandTest($webspaceKey, $locale, $dryRun, $urls);
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

    public function dataProviderHistory()
    {
        $webspaceKey = 'sulu_io';
        $locale = 'de';

        return array(
            array(
                $webspaceKey,
                $locale,
                true,
                array(
                    '/' => false,
                    '/my-team' => false,
                    '/my-team/daniel' => false,
                    '/my-team/johannes' => false,
                    '/team' => true,
                    '/team/daniel' => true,
                    '/team/johannes' => true
                ),
            ),
            array(
                $webspaceKey,
                $locale,
                false,
                array(
                    '/' => false,
                    '/my-team' => false,
                    '/my-team/daniel' => false,
                    '/my-team/johannes' => false,
                    '/team' => true,
                    '/team/daniel' => true,
                    '/team/johannes' => true
                ),
            ),
        );
    }

    /**
     * @dataProvider dataProviderHistory
     */
    public function testRunHistory($webspaceKey, $locale, $dryRun, $urls)
    {
        $this->initHistory($webspaceKey, $locale);

        $this->runCommandTest($webspaceKey, $locale, $dryRun, $urls);
    }

    private function runCommandTest($webspaceKey, $locale, $dryRun, $urls)
    {
        $this->tester->execute(array('webspaceKey' => $webspaceKey, 'locale' => $locale, '--dry-run' => $dryRun));
        $output = $this->tester->getDisplay();

        foreach ($urls as $url => $state) {
            $this->outputContains($output, $url, $state);
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

    private function outputIsDryRun($output)
    {
        $this->assertContains('Dry run complete', $output);
    }

    private function outputIsSaving($output)
    {
        $this->assertContains('Saving ...', $output);
    }
}
