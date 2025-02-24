<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Functional\UserInterface\Command;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

class InitializeHomepageCommandTest extends SuluTestCase
{
    private PageRepositoryInterface $pageRepository;

    protected function setUp(): void
    {
        $pageRepository = $this->getContainer()->get('sulu_page.page_repository');
        self::assertInstanceOf(PageRepositoryInterface::class, $pageRepository);
        $this->pageRepository = $pageRepository;
    }

    public function testExecuteWithNoExistingHomepage(): void
    {
        self::purgeDatabase();
        self::assertInstanceOf(KernelInterface::class, self::$kernel);
        $application = new Application(self::$kernel);

        self::assertSame(0, $this->pageRepository->countBy([
            'parentId' => null,
            'webspaceKey' => 'sulu-io',
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]));

        $command = $application->find('sulu:page:initialize');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        self::assertSame(1, $this->pageRepository->countBy([
            'parentId' => null,
            'webspaceKey' => 'sulu-io',
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]));
    }

    /**
     * @depends testExecuteWithNoExistingHomepage
     */
    public function testExecuteWithExistingHomepage(): void
    {
        self::assertInstanceOf(KernelInterface::class, self::$kernel);
        $application = new Application(self::$kernel);

        self::assertSame(1, $this->pageRepository->countBy([
            'parentId' => null,
            'webspaceKey' => 'sulu-io',
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]));

        $command = $application->find('sulu:page:initialize');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        self::assertSame(1, $this->pageRepository->countBy([
            'parentId' => null,
            'webspaceKey' => 'sulu-io',
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]));
    }
}
