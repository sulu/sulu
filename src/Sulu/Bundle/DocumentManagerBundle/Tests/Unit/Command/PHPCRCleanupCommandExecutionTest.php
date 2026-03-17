<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\Query\RowInterface;
use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Command\PHPCRCleanupCommand;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;
use Symfony\Component\Process\Process;

class PHPCRCleanupCommandExecutionTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<SessionInterface> */
    private ObjectProphecy $liveSession;

    /** @var ObjectProphecy<SessionInterface> */
    private ObjectProphecy $session;

    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;

    /** @var ObjectProphecy<ServicesResetter> */
    private ObjectProphecy $servicesResetter;

    protected function setUp(): void
    {
        $this->liveSession = $this->prophesize(SessionInterface::class);
        $this->session = $this->prophesize(SessionInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->servicesResetter = $this->prophesize(ServicesResetter::class);
    }

    public function testExecuteReturnsSuccessWhenSomeBatchesError(): void
    {
        $webspaceCollection = new WebspaceCollection();
        $webspaceCollection->setWebspaces([]);
        $this->webspaceManager->getWebspaceCollection()->willReturn($webspaceCollection);
        $this->webspaceManager->getAllLocalesByWebspaces()->willReturn([]);

        $this->configureQueryResult(['uuid-1']);
        $this->session->nodeExists('/cmf')->willReturn(false);
        $this->liveSession->nodeExists('/cmf')->willReturn(false);

        $process = $this->prophesize(Process::class);
        $process->start()->shouldBeCalledOnce();
        $process->wait()->willReturn(1);
        $process->getOutput()->willReturn(
            'PHPCR_CLEANUP_STATS:{"nodesProcessed":0,"nodesIgnored":0,"nodesErrored":1,"documents":0,"properties":0,"removedProperties":0,"removedStaleProperties":0}'
        );
        $process->getErrorOutput()->willReturn('node parsing failed');

        $this->servicesResetter->reset()->shouldBeCalledOnce();

        $command = $this->createCommandWithProcess($process->reveal());

        $tester = new CommandTester($command);
        $result = $tester->execute([
            '--dry-run' => true,
            '--processes' => 1,
            '--batch-size' => 1,
        ]);

        $this->assertSame(Command::SUCCESS, $result);
    }

    /**
     * @param string[] $uuids
     */
    private function configureQueryResult(array $uuids): void
    {
        $workspace = $this->prophesize(WorkspaceInterface::class);
        $queryManager = $this->prophesize(QueryManagerInterface::class);
        $query = $this->prophesize(QueryInterface::class);
        $queryResult = $this->prophesize(QueryResultInterface::class);

        $rows = [];
        foreach ($uuids as $uuid) {
            $row = $this->prophesize(RowInterface::class);
            $row->getValue('jcr:uuid')->willReturn($uuid);
            $rows[] = $row->reveal();
        }

        $this->session->getWorkspace()->willReturn($workspace->reveal());
        $workspace->getQueryManager()->willReturn($queryManager->reveal());
        $queryManager->createQuery(Argument::type('string'), 'JCR-SQL2')->willReturn($query->reveal());
        $query->execute()->willReturn($queryResult->reveal());
        $queryResult->getRows()->willReturn(new \ArrayIterator($rows));
    }

    private function createCommandWithProcess(Process $process): PHPCRCleanupCommand
    {
        return new class(
            $this->liveSession->reveal(),
            $this->session->reveal(),
            $this->webspaceManager->reveal(),
            $this->servicesResetter->reveal(),
            '/tmp/test-project',
            $process,
        ) extends PHPCRCleanupCommand {
            public function __construct(
                SessionInterface $liveSession,
                SessionInterface $session,
                WebspaceManagerInterface $webspaceManager,
                ServicesResetter $servicesResetter,
                string $projectDirectory,
                private Process $process,
            ) {
                parent::__construct($liveSession, $session, $webspaceManager, $servicesResetter, $projectDirectory);
            }

            /**
             * @param string[] $uuids
             */
            protected function createProcess(array $uuids, bool $dryRun, bool $debug): Process
            {
                return $this->process;
            }
        };
    }
}
