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
        $this->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Number of nodes per subprocess.', 50);
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

        $orphanedKeys = $this->getOrphanedWebspaceKeys();

        if ([] !== $orphanedKeys) {
            $io->warning(\sprintf(
                'Found orphaned webspaces in PHPCR: [%s]. Their trees will be removed after cleanup.',
                \implode(', ', $orphanedKeys),
            ));
        }

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
            'removedStaleProperties' => 0,
        ];

        $errorMessages = [];

        $io->section('Running cleanup process ...');

        $batchSize = (int) $input->getOption('batch-size');
        $parallelism = (int) $input->getOption('processes');
        Assert::greaterThan($batchSize, 0, 'Batch size must be greater than 0');
        Assert::greaterThan($parallelism, 0, 'Number of processes must be greater than 0');

        $batches = \array_chunk($uuids, $batchSize);
        $io->writeln(\sprintf('Processing %d nodes in %d batches (%d per batch, %d parallel)', \count($uuids), \count($batches), $batchSize, $parallelism));
        $io->newLine();

        $progressBar = $io->createProgressBar(\count($uuids));
        $progressBar->setFormat("Nodes: %nodes%\nIgnored: %ignoredNodes%\nErrored: %erroredNodes%\nDocuments: %documents%\nProperties: %properties%\nRemoved properties: %removedProperties%\nRemoved stale locale properties: %removedStaleProperties%\n\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%\n\n");

        $progressBar->setMessage((string) $stats['nodes'], 'nodes');
        $progressBar->setMessage((string) $stats['ignoredNodes'], 'ignoredNodes');
        $progressBar->setMessage((string) $stats['erroredNodes'], 'erroredNodes');
        $progressBar->setMessage((string) $stats['documents'], 'documents');
        $progressBar->setMessage((string) $stats['properties'], 'properties');
        $progressBar->setMessage((string) $stats['removedProperties'], 'removedProperties');
        $progressBar->setMessage((string) $stats['removedStaleProperties'], 'removedStaleProperties');

        $progressBar->start();

        $parallelGroups = \array_chunk($batches, $parallelism);

        foreach ($parallelGroups as $group) {
            $processes = [];

            foreach ($group as $index => $batchUuids) {
                $processes[$index] = $this->createProcess($batchUuids, $dryRun, $debug);
                $processes[$index]->start();
            }

            foreach ($processes as $index => $process) {
                $batchUuids = $group[$index];
                $batchNodeCount = \count($batchUuids);

                $status = $process->wait();

                if (PHPCRCleanupSingleNodeCommand::IGNORED === $status) {
                    $stats['nodes'] += $batchNodeCount;
                    $stats['ignoredNodes'] += $batchNodeCount;

                    $this->updateProgressBar($progressBar, $stats, $batchNodeCount);

                    continue;
                }

                if (0 !== $status) {
                    $stats['nodes'] += $batchNodeCount;
                    $stats['erroredNodes'] += $batchNodeCount;

                    $stderr = $process->getErrorOutput();
                    if ('' !== $stderr) {
                        $errorMessages[] = $stderr;
                    }

                    $this->logger->writeln(\sprintf(
                        "# Error processing batch of %d nodes\n\n%s\n",
                        $batchNodeCount,
                        $stderr,
                    ));

                    $this->updateProgressBar($progressBar, $stats, $batchNodeCount);

                    continue;
                }

                $subCommandOutput = $process->getOutput();

                \preg_match('/Nodes processed: (\d+)/', $subCommandOutput, $matches);
                $batchProcessed = (int) ($matches[1] ?? 0);
                \preg_match('/Nodes ignored: (\d+)/', $subCommandOutput, $matches);
                $batchIgnored = (int) ($matches[1] ?? 0);
                \preg_match('/Nodes errored: (\d+)/', $subCommandOutput, $matches);
                $batchErrored = (int) ($matches[1] ?? 0);

                $stats['nodes'] += $batchProcessed + $batchIgnored + $batchErrored;
                $stats['ignoredNodes'] += $batchIgnored;
                $stats['erroredNodes'] += $batchErrored;

                \preg_match('/Documents: (\d+)/', $subCommandOutput, $matches);
                $stats['documents'] += (int) ($matches[1] ?? 0);
                \preg_match('/Removed properties: (\d+)/', $subCommandOutput, $matches);
                $stats['removedProperties'] += (int) ($matches[1] ?? 0);
                \preg_match('/Total properties: (\d+)/', $subCommandOutput, $matches);
                $stats['properties'] += (int) ($matches[1] ?? 0);
                \preg_match('/Removed stale locale properties: (\d+)/', $subCommandOutput, $matches);
                $stats['removedStaleProperties'] += (int) ($matches[1] ?? 0);

                $stderr = $process->getErrorOutput();
                if ('' !== $stderr) {
                    $errorMessages[] = $stderr;
                }

                $this->logger->writeln($subCommandOutput);

                $this->updateProgressBar($progressBar, $stats, $batchProcessed + $batchIgnored + $batchErrored);
            }

            $this->servicesResetter->reset();
        }

        $progressBar->finish();

        $staleRoutes = $this->getStaleRouteLocales();
        $routesModified = false;
        if ([] !== $staleRoutes) {
            $io->section('Stale route locales detected');

            foreach ($staleRoutes as $webspaceKey => $staleLocales) {
                foreach ($staleLocales as $staleLocale) {
                    $routePath = \sprintf('/cmf/%s/routes/%s', $webspaceKey, $staleLocale);
                    $io->writeln(\sprintf('  Stale route tree: %s', $routePath));

                    if (!$dryRun) {
                        if ($this->session->nodeExists($routePath)) {
                            $this->session->getNode($routePath)->remove();
                            $routesModified = true;
                        }
                    }
                }
            }
        }

        if (!$dryRun && $routesModified) {
            $this->session->save();
        }

        if ([] !== $orphanedKeys) {
            $io->section('Removing orphaned webspace trees');

            foreach ($orphanedKeys as $orphanedKey) {
                $webspacePath = \sprintf('/cmf/%s', $orphanedKey);
                if ($this->session->nodeExists($webspacePath)) {
                    $io->writeln(\sprintf('  Removing orphaned webspace tree: %s', $webspacePath));
                    if (!$dryRun) {
                        $this->session->getNode($webspacePath)->remove();
                    }
                }
            }

            if (!$dryRun) {
                $this->session->save();
            }
        }

        $io->success('Cleanup process finished');

        $this->printErrors($errorMessages, $io, $output->isVerbose());

        if ($stats['ignoredNodes'] > 0) {
            $io->note(\sprintf('%d nodes were ignored (no matching structure metadata or locales).', $stats['ignoredNodes']));
        }

        return self::SUCCESS;
    }

    /**
     * @param string[] $uuids
     */
    protected function createProcess(array $uuids, bool $dryRun, bool $debug): Process
    {
        $executableFinder = new PhpExecutableFinder();
        $php = $executableFinder->find(false);

        $args = [$php, $_SERVER['argv'][0], PHPCRCleanupSingleNodeCommand::getDefaultName()];
        $args = \array_merge($args, $uuids);

        if ($dryRun) {
            $args[] = '--dry-run';
        }
        if ($debug) {
            $args[] = '--debug';
        }

        $process = new Process($args);
        $process->setTimeout(\max(120, \count($uuids) * 10));

        return $process;
    }

    /**
     * Finds webspace keys that exist in PHPCR but are not in webspace configuration.
     *
     * @return string[]
     */
    public function getOrphanedWebspaceKeys(): array
    {
        $configuredKeys = [];
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $configuredKeys[] = $webspace->getKey();
        }

        $reservedKeys = ['snippets', 'articles'];

        $orphanedKeys = [];
        $cmfNode = $this->session->getNode('/cmf');
        foreach ($cmfNode->getNodes() as $childNode) {
            $name = $childNode->getName();
            if (!\in_array($name, $configuredKeys, true) && !\in_array($name, $reservedKeys, true)) {
                $orphanedKeys[] = $name;
            }
        }

        return $orphanedKeys;
    }

    /**
     * Finds route locale directories in PHPCR that are not in webspace configuration.
     *
     * @return array<string, string[]> Webspace key => array of stale locale strings
     */
    public function getStaleRouteLocales(): array
    {
        $localesByWebspace = $this->webspaceManager->getAllLocalesByWebspaces();
        $staleRoutes = [];

        foreach ($localesByWebspace as $webspaceKey => $locales) {
            $routesPath = \sprintf('/cmf/%s/routes', $webspaceKey);

            if (!$this->session->nodeExists($routesPath)) {
                continue;
            }

            $routesNode = $this->session->getNode($routesPath);
            $configuredLocales = \array_keys($locales);

            foreach ($routesNode->getNodes() as $localeNode) {
                if (!\in_array($localeNode->getName(), $configuredLocales, true)) {
                    $staleRoutes[$webspaceKey][] = $localeNode->getName();
                }
            }
        }

        return $staleRoutes;
    }

    /**
     * @param array<int, mixed> $stats
     */
    private function updateProgressBar(\Symfony\Component\Console\Helper\ProgressBar $progressBar, array $stats, int $advance): void
    {
        $progressBar->setMessage((string) $stats['nodes'], 'nodes');
        $progressBar->setMessage((string) $stats['ignoredNodes'], 'ignoredNodes');
        $progressBar->setMessage((string) $stats['erroredNodes'], 'erroredNodes');
        $progressBar->setMessage((string) $stats['documents'], 'documents');
        $progressBar->setMessage((string) $stats['properties'], 'properties');
        $progressBar->setMessage((string) $stats['removedProperties'], 'removedProperties');
        $progressBar->setMessage((string) $stats['removedStaleProperties'], 'removedStaleProperties');
        $progressBar->advance($advance);
    }

    /**
     * @param string[] $errorMessages
     */
    private function printErrors(array $errorMessages, SymfonyStyle $io, bool $verbose): void
    {
        if ([] === $errorMessages) {
            return;
        }

        $io->section('Errors encountered during cleanup');
        if ($verbose) {
            foreach ($errorMessages as $errorMessage) {
                $io->writeln($errorMessage);
            }
        } else {
            $io->warning(\sprintf('%d batch(es) reported errors.', \count($errorMessages)));
            $io->note('To get more info about the errors rerun the command with the -v flag');
        }
    }
}
