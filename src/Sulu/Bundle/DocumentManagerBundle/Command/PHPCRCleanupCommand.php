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

namespace Sulu\Bundle\DocumentManagerBundle\Command;

use PHPCR\SessionInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
#[AsCommand(name: 'sulu:document:phpcr-cleanup', description: 'Cleanup the PHPCR repository and remove unused properties.')]
class PHPCRCleanupCommand extends Command
{
    private OutputInterface $logger;

    public function __construct(
        private SessionInterface $session,
        private WebspaceManagerInterface $webspaceManager,
        private ServicesResetter $servicesResetter,
        private string $projectDirectory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $defaultDebugFile = \sprintf('%s/var/%s_phpcr-cleanup.md', $this->projectDirectory, \date('Y-m-d-H-i-s'));

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not ask for confirmation.');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not make any changes to the repository.');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Write debug information to a file.');
        $this->addOption('debug-file', null, InputOption::VALUE_REQUIRED, 'Write debug information to a file.', $defaultDebugFile);
        $this->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Number of parallel processes.', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('PHPCR Cleanup');

        $dryRun = $input->getOption('dry-run');

        if (!$dryRun) {
            $io->warning('This command will remove properties from the PHPCR repository. Make sure to have a backup before running this command.');
            if (!$input->getOption('force')) {
                $answer = $io->askQuestion(new ConfirmationQuestion('Do you want to continue?'));

                if (!$answer) {
                    $io->warning('You have aborted the command');

                    return self::SUCCESS;
                }
            } else {
                $io->writeln('The command will wait for 5 seconds before starting');
                $progressBar = $io->createProgressBar(5);
                $progressBar->start();
                for ($i = 0; $i < 5; ++$i) {
                    $progressBar->advance();
                    \sleep(1);
                }
                $progressBar->finish();

                $io->newLine();
                $io->newLine();
                $io->newLine();
            }
        }

        $io->section('Initiating cleanup process ...');
        $io->writeln('Project directory: ' . $this->projectDirectory);
        $io->writeln('Dry-run: ' . ($dryRun ? 'enabled' : 'disabled'));

        $debug = $input->getOption('debug');
        $io->writeln('Debug: ' . ($debug ? 'enabled' : 'disabled'));

        $this->logger = new NullOutput();
        if ($debug) {
            $debugFile = $input->getOption('debug-file');
            $io->writeln('Debug file: ' . $debugFile);

            $resource = \fopen($debugFile, 'w');
            if (false === $resource) {
                throw new \RuntimeException(\sprintf('Could not open debug file "%s"', $debugFile));
            }

            $this->logger = new StreamOutput($resource);
        }

        $io->newLine();
        $io->newLine();

        $wheres = [];
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $wheres[] = \sprintf('(ISDESCENDANTNODE(page, "/cmf/%1$s/contents") OR ISSAMENODE(page, "/cmf/%1$s/contents"))', $webspace->getKey());
        }

        $wheres[] = 'page.[jcr:path] LIKE "/cmf/snippets/%/%"';
        $wheres[] = 'page.[jcr:path] LIKE "/cmf/articles/%/%/%"';

        $sql2 = \sprintf(
            'SELECT [jcr:uuid] FROM [nt:unstructured] AS page WHERE %s',
            \implode(' OR ', $wheres),
        );

        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $rows = $queryManager->createQuery($sql2, 'JCR-SQL2')->execute();

        $uuids = \array_map(static fn ($row) => $row->getValue('jcr:uuid'), \iterator_to_array($rows->getRows()));
        unset($rows);

        $stats = [
            'nodes' => 0,
            'ignoredNodes' => 0,
            'erroredNodes' => 0,
            'documents' => 0,
            'properties' => 0,
            'removedProperties' => 0,
        ];

        $ignoredUuids = [];
        $erroredUuids = [];

        $io->section('Running cleanup process ...');
        $progressBar = $io->createProgressBar(\count($uuids));
        $progressBar->setFormat("Nodes: %nodes%\nIgnored: %ignoredNodes%\nErrored: %erroredNodes%\nDocuments: %documents%\nProperties: %properties%\nRemoved properties: %removedProperties%\n\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%\n\n");

        $progressBar->setMessage((string) $stats['nodes'], 'nodes');
        $progressBar->setMessage((string) $stats['ignoredNodes'], 'ignoredNodes');
        $progressBar->setMessage((string) $stats['erroredNodes'], 'erroredNodes');
        $progressBar->setMessage((string) $stats['documents'], 'documents');
        $progressBar->setMessage((string) $stats['properties'], 'properties');
        $progressBar->setMessage((string) $stats['removedProperties'], 'removedProperties');

        $progressBar->start();

        $chunkSize = (int) $input->getOption('processes');
        Assert::greaterThan($chunkSize, 0, 'Chunk size must be greater than 0');

        $chunks = \array_chunk($uuids, $chunkSize);
        foreach ($chunks as $chunk) {
            $processes = [];

            /** @var string $uuid */
            foreach ($chunk as $uuid) {
                $processes[$uuid] = $this->createProcess($uuid, $dryRun, $debug);
                $processes[$uuid]->start();
            }

            foreach ($processes as $uuid => $process) {
                ++$stats['nodes'];
                $status = $process->wait();
                if (PHPCRCleanupSingleNodeCommand::IGNORED === $status) {
                    ++$stats['ignoredNodes'];
                    $ignoredUuids[] = $uuid;

                    continue;
                }
                if (0 !== $status) {
                    ++$stats['erroredNodes'];
                    $erroredUuids[$uuid] = $process->getErrorOutput();
                    $this->logger->writeln(\sprintf(
                        "# Error processing node '%s'\n\n%s\n",
                        $uuid,
                        $process->getErrorOutput(),
                    ));

                    continue;
                }

                $subCommandOutput = $process->getOutput();
                \preg_match('/Documents: (\d+)/', $subCommandOutput, $matches);
                $stats['documents'] += (int) ($matches[1] ?? 0);
                \preg_match('/Removed properties: (\d+)/', $subCommandOutput, $matches);
                $stats['removedProperties'] += (int) ($matches[1] ?? 0);
                \preg_match('/Total properties: (\d+)/', $subCommandOutput, $matches);
                $stats['properties'] += (int) ($matches[1] ?? 0);

                $this->logger->writeln($subCommandOutput);

                $progressBar->setMessage((string) $stats['nodes'], 'nodes');
                $progressBar->setMessage((string) $stats['ignoredNodes'], 'ignoredNodes');
                $progressBar->setMessage((string) $stats['erroredNodes'], 'erroredNodes');
                $progressBar->setMessage((string) $stats['documents'], 'documents');
                $progressBar->setMessage((string) $stats['properties'], 'properties');
                $progressBar->setMessage((string) $stats['removedProperties'], 'removedProperties');
                $progressBar->advance();
            }

            $this->servicesResetter->reset();
        }

        $progressBar->finish();
        $io->success('Cleanup process finished');

        $this->printErroredNodes($erroredUuids, $io, $output->isVerbose());

        if (0 < \count($ignoredUuids)) {
            $io->section('Following nodes are ignored');
            $io->listing($ignoredUuids);
        }

        return self::SUCCESS;
    }

    protected function createProcess(string $uuid, bool $dryRun, bool $debug): Process
    {
        $executableFinder = new PhpExecutableFinder();
        $php = $executableFinder->find(false);

        $process = new Process(\array_filter([
            $php,
            $_SERVER['argv'][0],
            PHPCRCleanupSingleNodeCommand::getDefaultName(),
            $uuid,
            $dryRun ? '--dry-run' : null,
            $debug ? '--debug' : null,
        ]));

        $process->setTimeout(120);

        return $process;
    }

    /**
     * @param array<string, string> $errors
     */
    private function printErroredNodes(array $errors, SymfonyStyle $io, bool $verbose): void
    {
        if ([] === $errors) {
            return;
        }

        $io->section('Following nodes are errored');
        if ($verbose) {
            foreach ($errors as $uuid => $errorMessage) {
                $io->error($uuid);
                $io->writeln($errorMessage);
            }
        } else {
            $io->listing(\array_keys($errors));
            $io->note('To get more info about the errors rerun the command with the -v flag');
        }
    }
}
