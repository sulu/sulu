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
use Psr\Cache\CacheItemPoolInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
    private string|false $phpBinary = false;

    public function __construct(
        private SessionInterface $liveSession,
        private SessionInterface $session,
        private WebspaceManagerInterface $webspaceManager,
        private ServicesResetter $servicesResetter,
        private string $projectDirectory,
        private ?CacheItemPoolInterface $phpcrNodesCachePool = null,
        private ?CacheItemPoolInterface $phpcrMetaCachePool = null,
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

        $orphanedKeys = $this->getOrphanedWebspaceKeys();

        if ([] !== $orphanedKeys) {
            $io->warning(\sprintf(
                'Found orphaned webspaces in PHPCR: [%s]. Their trees will be removed after cleanup.',
                \implode(', ', $orphanedKeys),
            ));
        }

        $uuids = $this->collectNodeUuids();
        $batchSize = (int) $input->getOption('batch-size');
        $parallelism = (int) $input->getOption('processes');
        Assert::greaterThan($batchSize, 0, 'Batch size must be greater than 0');
        Assert::greaterThan($parallelism, 0, 'Number of processes must be greater than 0');
        /** @var positive-int $batchSize */
        /** @var positive-int $parallelism */
        $cleanupResult = $this->runBatchedCleanup($uuids, $dryRun, $debug, $batchSize, $parallelism, $io);
        $stats = $cleanupResult['stats'];
        $errorMessages = $cleanupResult['errorMessages'];

        $this->removeStaleRouteTrees($this->getStaleRouteLocales(), $dryRun, $io);
        $this->removeOrphanedWebspaceTrees($orphanedKeys, $dryRun, $io);

        $io->success('Cleanup process finished');

        $this->printErrors($errorMessages, $io, $output->isVerbose());

        if ($stats['ignoredNodes'] > 0) {
            $io->note(\sprintf('%d nodes were ignored (no matching structure metadata or locales).', $stats['ignoredNodes']));
        }

        return self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function collectNodeUuids(): array
    {
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

        $uuids = [];
        foreach ($rows->getRows() as $row) {
            $uuid = $row->getValue('jcr:uuid');
            Assert::string($uuid);
            $uuids[] = $uuid;
        }
        unset($rows);

        return $uuids;
    }

    /**
     * @param string[] $uuids
     * @param positive-int $batchSize
     * @param positive-int $parallelism
     *
     * @return array{
     *     stats: array{nodes: int, ignoredNodes: int, erroredNodes: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int},
     *     errorMessages: string[]
     * }
     */
    private function runBatchedCleanup(
        array $uuids,
        bool $dryRun,
        bool $debug,
        int $batchSize,
        int $parallelism,
        SymfonyStyle $io,
    ): array {
        $io->section('Running cleanup process ...');

        $stats = $this->createInitialStats();
        $errorMessages = [];
        $batches = \array_chunk($uuids, $batchSize);

        $io->writeln(\sprintf(
            'Processing %d nodes in %d batches (%d per batch, %d parallel)',
            \count($uuids),
            \count($batches),
            $batchSize,
            $parallelism,
        ));
        $io->newLine();

        $progressBar = $this->createCleanupProgressBar($io, \count($uuids), $stats);
        $parallelGroups = \array_chunk($batches, $parallelism);

        foreach ($parallelGroups as $group) {
            /** @var array<int, array{process: Process, batchNodeCount: int}> $batchProcesses */
            $batchProcesses = [];

            foreach ($group as $batchUuids) {
                $process = $this->createProcess($batchUuids, $dryRun, $debug);
                $process->start();

                $batchProcesses[] = [
                    'process' => $process,
                    'batchNodeCount' => \count($batchUuids),
                ];
            }

            foreach ($batchProcesses as $batchProcess) {
                /** @var Process $process */
                $process = $batchProcess['process'];
                $batchNodeCount = $batchProcess['batchNodeCount'];
                $status = $process->wait();

                if (PHPCRCleanupSingleNodeCommand::IGNORED === $status) {
                    $stats['nodes'] += $batchNodeCount;
                    $stats['ignoredNodes'] += $batchNodeCount;
                    $this->updateProgressBar($progressBar, $stats, $batchNodeCount);

                    continue;
                }

                $subCommandOutput = $process->getOutput();
                $batchStats = $this->parseSubprocessOutput($subCommandOutput);
                $batchStatsResult = $this->applyBatchStats($stats, $batchStats, $batchNodeCount);
                $stats = $batchStatsResult['stats'];
                $progressAdvance = $batchStatsResult['advance'];

                $stderr = $process->getErrorOutput();

                if (0 !== $status) {
                    if ('' !== $stderr) {
                        $errorMessages[] = $stderr;
                    }
                    $this->logger->writeln(\sprintf(
                        "# Error processing batch of %d nodes\n\n%s\n",
                        $batchNodeCount,
                        $stderr,
                    ));
                } else {
                    $this->logger->writeln($subCommandOutput);
                }

                $this->updateProgressBar($progressBar, $stats, $progressAdvance);
            }

            $this->servicesResetter->reset();
            $this->phpcrNodesCachePool?->clear();
            $this->phpcrMetaCachePool?->clear();
        }

        $progressBar->finish();

        return [
            'stats' => $stats,
            'errorMessages' => $errorMessages,
        ];
    }

    /**
     * @param array<string, int> $stats
     */
    private function createCleanupProgressBar(SymfonyStyle $io, int $totalNodes, array $stats): ProgressBar
    {
        $progressBar = $io->createProgressBar($totalNodes);
        $progressBar->setFormat("Nodes: %nodes%\nIgnored: %ignoredNodes%\nErrored: %erroredNodes%\nDocuments: %documents%\nProperties: %properties%\nRemoved properties: %removedProperties%\nRemoved stale locale properties: %removedStaleProperties%\n\n%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%\n\n");

        $progressBar->setMessage((string) $stats['nodes'], 'nodes');
        $progressBar->setMessage((string) $stats['ignoredNodes'], 'ignoredNodes');
        $progressBar->setMessage((string) $stats['erroredNodes'], 'erroredNodes');
        $progressBar->setMessage((string) $stats['documents'], 'documents');
        $progressBar->setMessage((string) $stats['properties'], 'properties');
        $progressBar->setMessage((string) $stats['removedProperties'], 'removedProperties');
        $progressBar->setMessage((string) $stats['removedStaleProperties'], 'removedStaleProperties');
        $progressBar->start();

        return $progressBar;
    }

    /**
     * @return array{nodes: int, ignoredNodes: int, erroredNodes: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int}
     */
    private function createInitialStats(): array
    {
        return [
            'nodes' => 0,
            'ignoredNodes' => 0,
            'erroredNodes' => 0,
            'documents' => 0,
            'properties' => 0,
            'removedProperties' => 0,
            'removedStaleProperties' => 0,
        ];
    }

    /**
     * @param array{nodes: int, ignoredNodes: int, erroredNodes: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int} $stats
     * @param array{nodesProcessed: int, nodesIgnored: int, nodesErrored: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int} $batchStats
     *
     * @return array{
     *     stats: array{nodes: int, ignoredNodes: int, erroredNodes: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int},
     *     advance: int
     * }
     */
    private function applyBatchStats(array $stats, array $batchStats, int $batchNodeCount): array
    {
        $parsedTotal = $batchStats['nodesProcessed'] + $batchStats['nodesIgnored'] + $batchStats['nodesErrored'];
        if ($parsedTotal > 0) {
            $stats['nodes'] += $parsedTotal;
            $stats['ignoredNodes'] += $batchStats['nodesIgnored'];
            $stats['erroredNodes'] += $batchStats['nodesErrored'];
            $stats['documents'] += $batchStats['documents'];
            $stats['removedProperties'] += $batchStats['removedProperties'];
            $stats['properties'] += $batchStats['properties'];
            $stats['removedStaleProperties'] += $batchStats['removedStaleProperties'];

            return [
                'stats' => $stats,
                'advance' => $parsedTotal,
            ];
        }

        $stats['nodes'] += $batchNodeCount;
        $stats['erroredNodes'] += $batchNodeCount;

        return [
            'stats' => $stats,
            'advance' => $batchNodeCount,
        ];
    }

    /**
     * @param array<string, string[]> $staleRoutes
     */
    private function removeStaleRouteTrees(array $staleRoutes, bool $dryRun, SymfonyStyle $io): void
    {
        if ([] === $staleRoutes) {
            return;
        }

        $io->section('Stale route locales detected');

        $routesModified = false;
        foreach ($staleRoutes as $webspaceKey => $staleLocales) {
            foreach ($staleLocales as $staleLocale) {
                $routePath = \sprintf('/cmf/%s/routes/%s', $webspaceKey, $staleLocale);
                $io->writeln(\sprintf('  Stale route tree: %s', $routePath));

                if ($dryRun) {
                    continue;
                }

                foreach ($this->getPhpcrSessions() as $phpcrSession) {
                    if ($phpcrSession->nodeExists($routePath)) {
                        $phpcrSession->getNode($routePath)->remove();
                        $routesModified = true;
                    }
                }
            }
        }

        if (!$dryRun && $routesModified) {
            $this->savePhpcrSessions();
        }
    }

    /**
     * @param string[] $orphanedKeys
     */
    private function removeOrphanedWebspaceTrees(array $orphanedKeys, bool $dryRun, SymfonyStyle $io): void
    {
        if ([] === $orphanedKeys) {
            return;
        }

        $io->section('Removing orphaned webspace trees');

        $orphanedModified = false;
        foreach ($orphanedKeys as $orphanedKey) {
            $webspacePath = \sprintf('/cmf/%s', $orphanedKey);
            foreach ($this->getPhpcrSessions() as $phpcrSession) {
                if (!$phpcrSession->nodeExists($webspacePath)) {
                    continue;
                }

                $io->writeln(\sprintf('  Removing orphaned webspace tree: %s', $webspacePath));
                if (!$dryRun) {
                    $phpcrSession->getNode($webspacePath)->remove();
                    $orphanedModified = true;
                }
            }
        }

        if (!$dryRun && $orphanedModified) {
            $this->savePhpcrSessions();
        }
    }

    /**
     * @param string[] $uuids
     */
    protected function createProcess(array $uuids, bool $dryRun, bool $debug): Process
    {
        if (false === $this->phpBinary) {
            $php = (new PhpExecutableFinder())->find(false);
            if (false === $php) {
                throw new \RuntimeException('Could not find PHP executable.');
            }
            $this->phpBinary = $php;
        }

        $args = [$this->phpBinary, $_SERVER['argv'][0], PHPCRCleanupSingleNodeCommand::getDefaultName()];
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
     * @return array{nodesProcessed: int, nodesIgnored: int, nodesErrored: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int}
     */
    private function parseSubprocessOutput(string $output): array
    {
        if (\preg_match('/PHPCR_CLEANUP_STATS:(.+)$/m', $output, $matches)) {
            $data = \json_decode($matches[1], true);
            if (\is_array($data)) {
                /** @var array{nodesProcessed: int, nodesIgnored: int, nodesErrored: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int} $merged */
                $merged = \array_merge(PHPCRCleanupSingleNodeCommand::createEmptyBatchStats(), $data);

                return $merged;
            }
        }

        return PHPCRCleanupSingleNodeCommand::createEmptyBatchStats();
    }

    /**
     * Finds webspace keys that exist in PHPCR but are not in webspace configuration.
     *
     * @return string[]
     */
    public function getOrphanedWebspaceKeys(): array
    {
        if (!$this->session->nodeExists('/cmf') && !$this->liveSession->nodeExists('/cmf')) {
            return [];
        }

        $configuredKeys = [];
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            $configuredKeys[] = $webspace->getKey();
        }

        $reservedKeys = ['snippets', 'articles'];
        $orphanedKeys = [];
        foreach ($this->getPhpcrSessions() as $session) {
            foreach ($this->getOrphanedWebspaceKeysForSession($session, $configuredKeys, $reservedKeys) as $key) {
                $orphanedKeys[$key] = true;
            }
        }

        return \array_keys($orphanedKeys);
    }

    /**
     * @param string[] $configuredKeys
     * @param string[] $reservedKeys
     *
     * @return string[]
     */
    private function getOrphanedWebspaceKeysForSession(
        SessionInterface $session,
        array $configuredKeys,
        array $reservedKeys,
    ): array {
        if (!$session->nodeExists('/cmf')) {
            return [];
        }

        $orphanedKeys = [];
        $cmfNode = $session->getNode('/cmf');
        foreach ($cmfNode->getNodes() as $childNode) {
            $name = $childNode->getName();
            if (\in_array($name, $configuredKeys, true) || \in_array($name, $reservedKeys, true)) {
                continue;
            }

            // Only treat as orphaned webspace if it has a webspace-like structure.
            if (!$childNode->hasNode('contents') && !$childNode->hasNode('routes')) {
                continue;
            }

            $orphanedKeys[] = $name;
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
        $staleRoutesByWebspace = [];
        foreach ($this->getPhpcrSessions() as $session) {
            foreach ($this->getStaleRouteLocalesForSession($session, $localesByWebspace) as $webspaceKey => $staleLocales) {
                foreach ($staleLocales as $staleLocale) {
                    $staleRoutesByWebspace[$webspaceKey][$staleLocale] = true;
                }
            }
        }

        $staleRoutes = [];
        foreach ($staleRoutesByWebspace as $webspaceKey => $staleLocalesMap) {
            $staleRoutes[$webspaceKey] = \array_keys($staleLocalesMap);
        }

        return $staleRoutes;
    }

    /**
     * @return SessionInterface[]
     */
    private function getPhpcrSessions(): array
    {
        return [$this->session, $this->liveSession];
    }

    private function savePhpcrSessions(): void
    {
        $this->session->save();
        $this->liveSession->save();
    }

    /**
     * @param array<string, array<string, mixed>> $localesByWebspace
     *
     * @return array<string, string[]>
     */
    private function getStaleRouteLocalesForSession(SessionInterface $session, array $localesByWebspace): array
    {
        $staleRoutes = [];

        foreach ($localesByWebspace as $webspaceKey => $locales) {
            $routesPath = \sprintf('/cmf/%s/routes', $webspaceKey);

            if (!$session->nodeExists($routesPath)) {
                continue;
            }

            $routesNode = $session->getNode($routesPath);
            $configuredLocalesMap = \array_flip(\array_keys($locales));

            foreach ($routesNode->getNodes() as $localeNode) {
                if (!isset($configuredLocalesMap[$localeNode->getName()])) {
                    $staleRoutes[$webspaceKey][] = $localeNode->getName();
                }
            }
        }

        return $staleRoutes;
    }

    /**
     * @param array<string, int> $stats
     */
    private function updateProgressBar(ProgressBar $progressBar, array $stats, int $advance): void
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
