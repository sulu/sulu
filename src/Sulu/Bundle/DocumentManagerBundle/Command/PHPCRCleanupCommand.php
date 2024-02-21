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

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\HttpCacheBundle\EventSubscriber\InvalidationSubscriber;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Subscriber\PHPCR\CleanupNode;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PHPCRCleanupCommand extends Command
{
    /**
     * @var string[]
     */
    public const WHITELIST = [
        'state',
        'published',
        'created',
        'creator',
        'changed',
        'changer',
    ];

    protected static $defaultName = 'sulu:phpcr:cleanup';

    private array $aliasMapping = [];

    private string $languagePrefix;

    /**
     * @var array<string, OptionsResolver>
     */
    private array $optionsResolvers = [];

    private OutputInterface $logger;

    public function __construct(
        private SessionInterface $liveSession,
        private SessionInterface $session,
        NamespaceRegistry $namespaceRegistry,
        private EventDispatcherInterface $documentManagerEventDispatcher,
        private DocumentManagerInterface $documentManager,
        private StructureMetadataFactoryInterface $structureMetaDataFactory,
        private InvalidationSubscriber $invalidationSubscriber,
        private string $projectDirectory,
        array $mapping,
    ) {
        parent::__construct();

        $this->languagePrefix = $namespaceRegistry->getPrefix('system_localized');
        foreach ($mapping as $item) {
            $this->aliasMapping[$item['phpcr_type']] = $item['alias'];
        }
    }

    protected function configure(): void
    {
        $defaultDebugFile = \sprintf('%s/var/%s_phpcr-cleanup.md', $this->projectDirectory, \date('Y-m-d-H-i-s'));

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not ask for confirmation.');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not make any changes to the repository.');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Write debug information to a file.');
        $this->addOption('debug-file', null, InputOption::VALUE_REQUIRED, 'Write debug information to a file.', $defaultDebugFile);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('PHPCR Cleanup');

        $dryRun = $input->getOption('dry-run');

        if (!$dryRun) {
            $io->warning('This command will remove properties from the PHPCR repository. Make sure to have a backup before running this command.');
            if (!$input->getOption('force')) {
                $answer = $io->ask('Do you want to continue [y/n]', null, function ($value) {
                    $value = \strtolower($value);
                    if (!\in_array($value, ['y', 'n'], true)) {
                        throw new \RuntimeException('You need to enter "y" to continue or "n" to abort.');
                    }

                    return 'y' === $value;
                });

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
        if ($input->getOption('debug')) {
            $debugFile = $input->getOption('debug-file');
            $io->writeln('Debug file: ' . $debugFile);

            $this->logger = new StreamOutput(\fopen($debugFile, 'w'));
        }

        $io->newLine();
        $io->newLine();

        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $rows = $queryManager->createQuery('SELECT * FROM [nt:unstructured]', 'JCR-SQL2')->execute();

        $stats = [
            'nodes' => 0,
            'ignoredNodes' => 0,
            'properties' => 0,
            'removedProperties' => 0,
        ];

        $io->section('Running cleanup process ...');
        $progressBar = $io->createProgressBar();
        $progressBar->setFormat("Nodes: %nodes%\nIngored: %ignoredNodes%\nProperties: %properties%\nRemoved properties: %removedProperties%\n\n");

        $progressBar->setMessage((string) $stats['nodes'], 'nodes');
        $progressBar->setMessage((string) $stats['ignoredNodes'], 'ignoredNodes');
        $progressBar->setMessage((string) $stats['properties'], 'properties');
        $progressBar->setMessage((string) $stats['removedProperties'], 'removedProperties');

        $progressBar->start();

        foreach ($rows->getNodes() as $node) {
            $alias = $this->getAliasForNode($node);
            if (null === $alias || !$this->structureMetaDataFactory->hasStructuresFor($alias)) {
                continue;
            }

            ++$stats['nodes'];

            foreach ($this->getLocales($node) as $locale) {
                if (!$node->hasProperty($this->languagePrefix . ':' . $locale . '-template')) {
                    continue;
                }

                $document = $this->documentManager->find($node->getIdentifier(), $locale);

                $workflowStage = WorkflowStage::TEST;
                if ($document instanceof WorkflowStageBehavior) {
                    $workflowStage = $document->getWorkflowStage();
                }

                try {
                    $defaultCleanupNode = new CleanupNode(clone $node);
                    $this->persist($document, $defaultCleanupNode, $locale);

                    $liveCleanupNode = new CleanupNode(clone $node);
                    if (WorkflowStage::PUBLISHED === $workflowStage) {
                        $this->publish($document, $liveCleanupNode, $locale);
                    }

                    $this->documentManager->clear();
                } catch (\Exception $e) {
                    ++$stats['ignoredNodes'];

                    continue;
                }

                $writtenProperties = $defaultCleanupNode->getWrittenPropertyKeys();
                foreach ($this->cleanupNode($node, $locale, $writtenProperties, $dryRun) as $result) {
                    ++$stats['properties'];
                    $stats['removedProperties'] += $result ? 1 : 0;
                }

                $liveNode = $this->liveSession->getNode($node->getPath());
                $writtenProperties = $liveCleanupNode->getWrittenPropertyKeys();
                foreach ($this->cleanupNode($liveNode, $locale, $writtenProperties, $dryRun) as $result) {
                    ++$stats['properties'];
                    $stats['removedProperties'] += $result ? 1 : 0;
                }

                $this->session->save();
                $this->documentManager->clear();
            }

            $progressBar->setMessage((string) $stats['nodes'], 'nodes');
            $progressBar->setMessage((string) $stats['ignoredNodes'], 'ignoredNodes');
            $progressBar->setMessage((string) $stats['properties'], 'properties');
            $progressBar->setMessage((string) $stats['removedProperties'], 'removedProperties');
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->success('Cleanup process finished');

        return self::SUCCESS;
    }

    private function persist($document, CleanupNode $cleanupNode, string $locale): void
    {
        $options = $this->getOptionsResolver(Events::PERSIST)->resolve();
        $event = new Event\PersistEvent($document, $locale, $options);
        $event->setNode($cleanupNode);
        $this->documentManagerEventDispatcher->dispatch($event, Events::PERSIST);
    }

    private function publish($document, CleanupNode $cleanupNode, string $locale): void
    {
        $this->invalidationSubscriber->deactivate();
        $options = $this->getOptionsResolver(Events::PUBLISH)->resolve();
        $event = new Event\PublishEvent($document, $locale, $options);
        $event->setNode($cleanupNode);
        $this->documentManagerEventDispatcher->dispatch($event, Events::PUBLISH);
        $this->invalidationSubscriber->activate();
    }

    private function cleanupNode(NodeInterface $node, string $locale, array $writtenProperties, bool $dryRun): \Generator
    {
        $this->logger->writeln(\sprintf("# Cleaning up node \"%s\" for locale \"%s\" in workspace \"%s\"\n", $node->getPath(), $locale, $node->getSession()->getWorkspace()->getName()));

        $whiteList = \array_map(fn ($property) => $this->languagePrefix . ':' . $locale . '-' . $property, self::WHITELIST);
        $this->logger->writeln(\sprintf("Whitelisted:\n* %s\n", \implode("\n* ", $whiteList)));
        $this->logger->writeln(\sprintf("Written:\n* %s\n", \implode("\n* ", $writtenProperties)));

        $removedProperties = [];
        foreach ($node->getProperties() as $property) {
            if (!\str_starts_with($property->getName(), $this->languagePrefix . ':' . $locale)) {
                yield false;

                continue;
            }

            if (\in_array($property->getName(), $writtenProperties, true)
                || \in_array($property->getName(), $whiteList, true)
            ) {
                yield false;

                continue;
            }

            $removedProperties[] = $property->getName();
            if (!$dryRun) {
                $property->remove();
            }

            yield true;
        }

        $this->logger->writeln(\sprintf("Removed:\n* %s\n", \implode("\n* ", $removedProperties)));
    }

    private function getOptionsResolver(string $eventName): OptionsResolver
    {
        if (isset($this->optionsResolvers[$eventName])) {
            return $this->optionsResolvers[$eventName];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefault('locale', null);

        $event = new Event\ConfigureOptionsEvent($resolver);
        $this->documentManagerEventDispatcher->dispatch($event, Events::CONFIGURE_OPTIONS);

        $this->optionsResolvers[$eventName] = $resolver;

        return $resolver;
    }

    private function getLocales(NodeInterface $node)
    {
        $locales = [];

        foreach ($node->getProperties() as $property) {
            \preg_match(
                \sprintf('/^%s:([a-zA-Z_]*?)-.*/', $this->languagePrefix),
                $property->getName(),
                $matches,
            );

            if ($matches) {
                $locales[$matches[1]] = $matches[1];
            }
        }

        return \array_values(\array_unique($locales));
    }

    private function getAliasForNode(NodeInterface $node): ?string
    {
        foreach ($node->getMixinNodeTypes() as $mixinNodeType) {
            if (isset($this->aliasMapping[$mixinNodeType->getName()])) {
                return $this->aliasMapping[$mixinNodeType->getName()];
            }
        }

        return null;
    }
}
