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
use PHPCR\PathNotFoundException;
use PHPCR\SessionInterface;
use Sulu\Bundle\HttpCacheBundle\EventSubscriber\InvalidationSubscriber;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\Subscriber\PHPCR\CleanupNode;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
#[AsCommand(name: 'sulu:document:phpcr-cleanup-single-node', description: 'Cleanup PHPCR node(s) and remove unused properties.')]
class PHPCRCleanupSingleNodeCommand extends Command
{
    public const IGNORED = 101;

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

    private string $languagePrefix;

    /**
     * @var array<string, string>
     */
    private array $aliasMapping = [];

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $cachedLocalesByWebspaces = null;

    /**
     * @var array<string, OptionsResolver>
     */
    private array $optionsResolvers = [];

    private OutputInterface $logger;

    /**
     * @param array<array{phpcr_type: string, alias: string}> $mapping
     */
    public function __construct(
        private SessionInterface $liveSession,
        private SessionInterface $session,
        private StructureMetadataFactoryInterface $structureMetaDataFactory,
        NamespaceRegistry $namespaceRegistry,
        private EventDispatcherInterface $documentManagerEventDispatcher,
        private DocumentManagerInterface $documentManager,
        array $mapping,
        private WebspaceManagerInterface $webspaceManager,
    ) {
        parent::__construct();

        $this->logger = new NullOutput();

        $this->languagePrefix = $namespaceRegistry->getPrefix('system_localized');
        foreach ($mapping as $item) {
            $this->aliasMapping[$item['phpcr_type']] = $item['alias'];
        }
    }

    protected function configure(): void
    {
        $this->addArgument('node', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Node identifier(s) to cleanup.');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not make any changes to the repository.');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Write debug information to a file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $debug = $input->getOption('debug');
        if ($debug) {
            $this->logger = $output;
        }

        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $uuids = $input->getArgument('node');

        $totalStats = self::createEmptyBatchStats();

        foreach ($uuids as $uuid) {
            try {
                $nodeStats = $this->processNode($uuid, $dryRun);

                if (null === $nodeStats) {
                    ++$totalStats['nodesIgnored'];

                    continue;
                }

                ++$totalStats['nodesProcessed'];
                $totalStats = $this->mergeNodeStatsIntoTotal($totalStats, $nodeStats);
            } catch (\Exception $e) {
                ++$totalStats['nodesErrored'];
                \fwrite(\STDERR, (string) $e);
                $this->session->refresh(false);
                $this->liveSession->refresh(false);
            }

            $this->documentManager->clear();
        }

        $io->listing([
            'Nodes processed: ' . $totalStats['nodesProcessed'],
            'Nodes ignored: ' . $totalStats['nodesIgnored'],
            'Nodes errored: ' . $totalStats['nodesErrored'],
            'Documents: ' . $totalStats['documents'],
            'Removed stale locale properties: ' . $totalStats['removedStaleProperties'],
            'Removed properties: ' . $totalStats['removedProperties'],
            'Total properties: ' . $totalStats['properties'],
        ]);

        $output->writeln('PHPCR_CLEANUP_STATS:' . \json_encode($totalStats, \JSON_THROW_ON_ERROR));

        if ($totalStats['nodesErrored'] > 0) {
            return self::FAILURE;
        }

        if ($totalStats['nodesProcessed'] > 0) {
            return self::SUCCESS;
        }

        return self::IGNORED;
    }

    /**
     * @return array{nodesProcessed: int, nodesIgnored: int, nodesErrored: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int}
     */
    public static function createEmptyBatchStats(): array
    {
        return [
            'nodesProcessed' => 0,
            'nodesIgnored' => 0,
            'nodesErrored' => 0,
            'documents' => 0,
            'properties' => 0,
            'removedProperties' => 0,
            'removedStaleProperties' => 0,
        ];
    }

    /**
     * @param array{nodesProcessed: int, nodesIgnored: int, nodesErrored: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int} $totalStats
     * @param array{documents: int, properties: int, removedProperties: int, removedStaleProperties: int} $nodeStats
     *
     * @return array{nodesProcessed: int, nodesIgnored: int, nodesErrored: int, documents: int, properties: int, removedProperties: int, removedStaleProperties: int}
     */
    private function mergeNodeStatsIntoTotal(array $totalStats, array $nodeStats): array
    {
        $totalStats['documents'] += $nodeStats['documents'];
        $totalStats['properties'] += $nodeStats['properties'];
        $totalStats['removedProperties'] += $nodeStats['removedProperties'];
        $totalStats['removedStaleProperties'] += $nodeStats['removedStaleProperties'];

        return $totalStats;
    }

    /**
     * @return array{documents: int, properties: int, removedProperties: int, removedStaleProperties: int}|null
     */
    private function processNode(string $uuid, bool $dryRun): ?array
    {
        $stats = [
            'documents' => 0,
            'properties' => 0,
            'removedProperties' => 0,
            'removedStaleProperties' => 0,
        ];

        $node = $this->session->getNodeByIdentifier($uuid);
        $alias = $this->getAliasForNode($node);
        if (null === $alias || !$this->structureMetaDataFactory->hasStructuresFor($alias)) {
            return null;
        }

        $nodeLocales = $this->getLocales($node);
        if (0 === \count($nodeLocales)) {
            return null;
        }

        $validLocales = $this->getValidLocales($node);
        $staleLocales = \array_diff($nodeLocales, $validLocales);
        $activeLocales = \array_intersect($nodeLocales, $validLocales);

        $defaultSessionModified = false;
        $liveSessionModified = false;
        $liveNode = $this->getLiveNodeByPathOrNull($node->getPath());

        $staleLocaleCleanupResult = $this->applyStaleLocaleCleanup(
            $node,
            $liveNode,
            $staleLocales,
            $dryRun,
            $stats,
        );
        $stats = $staleLocaleCleanupResult['stats'];
        $defaultSessionModified = $staleLocaleCleanupResult['defaultSessionModified'];
        $liveSessionModified = $staleLocaleCleanupResult['liveSessionModified'];

        $shadowCleanupResult = $this->applyInvalidShadowCleanup(
            $node,
            $liveNode,
            $validLocales,
            $dryRun,
            $defaultSessionModified,
            $liveSessionModified,
        );
        $defaultSessionModified = $shadowCleanupResult['defaultSessionModified'];
        $liveSessionModified = $shadowCleanupResult['liveSessionModified'];

        // Only save now if there are no active locales to process (the per-locale loop saves after each locale)
        if (!$dryRun && [] === $activeLocales) {
            $this->saveModifiedSessions($defaultSessionModified, $liveSessionModified);
        }

        foreach ($activeLocales as $locale) {
            ++$stats['documents'];

            $document = $this->documentManager->find($uuid, $locale);

            $workflowStage = WorkflowStage::TEST;
            if ($document instanceof WorkflowStageBehavior) {
                $workflowStage = $document->getWorkflowStage();
            }

            Assert::isInstanceOf($document, UuidBehavior::class);

            $node = $this->session->getNodeByIdentifier($document->getUuid());
            $defaultCleanupNode = new CleanupNode(clone $node);
            $this->persist($document, $defaultCleanupNode, $locale);

            $wasPublished = WorkflowStage::PUBLISHED === $workflowStage;
            $liveCleanupNode = null;
            if ($wasPublished) {
                $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
                $liveCleanupNode = new CleanupNode(clone $liveNode);
                $this->publish($document, $liveCleanupNode, $locale);
            }

            $this->documentManager->clear();

            /** @var string[] $writtenProperties */
            $writtenProperties = $defaultCleanupNode->getWrittenPropertyKeys();
            foreach ($this->cleanupNode($node, $locale, $writtenProperties, $dryRun) as $result) {
                ++$stats['properties'];
                $stats['removedProperties'] += $result ? 1 : 0;
            }

            if (!$dryRun) {
                $this->session->save();
                $defaultSessionModified = false;
            }

            if ($wasPublished) {
                $liveNode = $this->liveSession->getNode($node->getPath());
                /** @var string[] $writtenProperties */
                $writtenProperties = $liveCleanupNode->getWrittenPropertyKeys();
                foreach ($this->cleanupNode($liveNode, $locale, $writtenProperties, $dryRun) as $result) {
                    ++$stats['properties'];
                    $stats['removedProperties'] += $result ? 1 : 0;
                }

                if (!$dryRun) {
                    $this->liveSession->save();
                    $liveSessionModified = false;
                }
            }
        }

        if (!$dryRun) {
            $this->saveModifiedSessions($defaultSessionModified, $liveSessionModified);
        }

        return $stats;
    }

    /**
     * @param string[] $staleLocales
     * @param array{documents: int, properties: int, removedProperties: int, removedStaleProperties: int} $stats
     *
     * @return array{
     *     stats: array{documents: int, properties: int, removedProperties: int, removedStaleProperties: int},
     *     defaultSessionModified: bool,
     *     liveSessionModified: bool
     * }
     */
    private function applyStaleLocaleCleanup(
        NodeInterface $node,
        ?NodeInterface $liveNode,
        array $staleLocales,
        bool $dryRun,
        array $stats,
    ): array {
        if ([] === $staleLocales) {
            return [
                'stats' => $stats,
                'defaultSessionModified' => false,
                'liveSessionModified' => false,
            ];
        }

        $this->logger->writeln(\sprintf(
            "# Removing stale locales [%s] from node \"%s\"\n",
            \implode(', ', $staleLocales),
            $node->getPath(),
        ));

        $removedProperties = $this->removeStaleLocaleProperties($node, $staleLocales, $dryRun);
        $stats['removedStaleProperties'] += $removedProperties;
        $defaultSessionModified = $removedProperties > 0;
        $liveSessionModified = false;

        if (null !== $liveNode) {
            $removedLiveProperties = $this->removeStaleLocaleProperties($liveNode, $staleLocales, $dryRun);
            $stats['removedStaleProperties'] += $removedLiveProperties;
            $liveSessionModified = $removedLiveProperties > 0;
        }

        return [
            'stats' => $stats,
            'defaultSessionModified' => $defaultSessionModified,
            'liveSessionModified' => $liveSessionModified,
        ];
    }

    /**
     * @param string[] $validLocales
     *
     * @return array{defaultSessionModified: bool, liveSessionModified: bool}
     */
    private function applyInvalidShadowCleanup(
        NodeInterface $node,
        ?NodeInterface $liveNode,
        array $validLocales,
        bool $dryRun,
        bool $defaultSessionModified,
        bool $liveSessionModified,
    ): array {
        $cleanedShadows = $this->cleanInvalidShadowReferences($node, $validLocales, $dryRun);
        if (0 === $cleanedShadows) {
            return [
                'defaultSessionModified' => $defaultSessionModified,
                'liveSessionModified' => $liveSessionModified,
            ];
        }

        $defaultSessionModified = true;

        if (null !== $liveNode) {
            $cleanedLiveShadows = $this->cleanInvalidShadowReferences($liveNode, $validLocales, $dryRun);
            $liveSessionModified = $liveSessionModified || $cleanedLiveShadows > 0;
        }

        return [
            'defaultSessionModified' => $defaultSessionModified,
            'liveSessionModified' => $liveSessionModified,
        ];
    }

    private function getLiveNodeByPathOrNull(string $path): ?NodeInterface
    {
        try {
            return $this->liveSession->getNode($path);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    private function saveModifiedSessions(bool $defaultSessionModified, bool $liveSessionModified): void
    {
        if ($defaultSessionModified) {
            $this->session->save();
        }

        if ($liveSessionModified) {
            $this->liveSession->save();
        }
    }

    private function persist(object $document, CleanupNode $cleanupNode, string $locale): void
    {
        $options = $this->getOptionsResolver(Events::PERSIST)->resolve();
        $event = new Event\PersistEvent($document, $locale, $options);
        $event->setNode($cleanupNode);
        $this->documentManagerEventDispatcher->dispatch($event, Events::PERSIST);
    }

    private function publish(object $document, CleanupNode $cleanupNode, string $locale): void
    {
        $options = $this->getOptionsResolver(Events::PUBLISH)->resolve();
        $options[InvalidationSubscriber::HTTP_CACHE_INVALIDATION_OPTION] = false;
        $event = new Event\PublishEvent($document, $locale, $options);
        $event->setNode($cleanupNode);
        $this->documentManagerEventDispatcher->dispatch($event, Events::PUBLISH);
    }

    /**
     * @param string[] $writtenProperties
     */
    private function cleanupNode(NodeInterface $node, string $locale, array $writtenProperties, bool $dryRun): \Generator
    {
        $this->logger->writeln(\sprintf("# Cleaning up node \"%s\" for locale \"%s\" in workspace \"%s\"\n", $node->getPath(), $locale, $node->getSession()->getWorkspace()->getName()));

        $whiteList = \array_map(fn ($property) => $this->languagePrefix . ':' . $locale . '-' . $property, self::WHITELIST);

        $removedProperties = [];
        foreach ($node->getProperties() as $property) {
            if (!\str_starts_with($property->getName(), $this->languagePrefix . ':' . $locale . '-')) {
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

        if ([] !== $removedProperties) {
            $this->logger->writeln(\sprintf("Removed properties:\n* %s\n", \implode("\n* ", $removedProperties)));
        } else {
            $this->logger->writeln("No properties to remove.\n");
        }
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

    /**
     * Determines which locales are valid for a given node based on webspace configuration.
     *
     * @return string[]
     */
    public function getValidLocales(NodeInterface $node): array
    {
        $segments = \explode('/', \trim($node->getPath(), '/'));

        if (\count($segments) < 2) {
            return [];
        }

        $key = $segments[1]; // segment after 'cmf': webspace key, 'snippets', or 'articles'

        if ('snippets' === $key || 'articles' === $key) {
            return $this->webspaceManager->getAllLocales();
        }

        $this->cachedLocalesByWebspaces ??= $this->webspaceManager->getAllLocalesByWebspaces();

        return \array_keys($this->cachedLocalesByWebspaces[$key] ?? []);
    }

    /**
     * Removes all localized properties for the given stale locales from a node.
     *
     * @param string[] $staleLocales
     *
     * @return int Number of properties removed (or that would be removed in dry-run)
     */
    public function removeStaleLocaleProperties(NodeInterface $node, array $staleLocales, bool $dryRun): int
    {
        if ([] === $staleLocales) {
            return 0;
        }

        $removedCount = 0;
        $staleLocalesMap = \array_flip($staleLocales);
        $localizedPrefix = $this->languagePrefix . ':';
        $localizedPrefixLength = \strlen($localizedPrefix);

        foreach ($node->getProperties() as $property) {
            $propertyName = $property->getName();

            if (!\str_starts_with($propertyName, $localizedPrefix)) {
                continue;
            }

            $dashPosition = \strpos($propertyName, '-', $localizedPrefixLength);
            if (false === $dashPosition) {
                continue;
            }

            $locale = \substr($propertyName, $localizedPrefixLength, $dashPosition - $localizedPrefixLength);
            if (!isset($staleLocalesMap[$locale])) {
                continue;
            }

            ++$removedCount;
            $this->logger->writeln(\sprintf('  Removing stale property: %s', $propertyName));

            if (!$dryRun) {
                $property->remove();
            }
        }

        return $removedCount;
    }

    /**
     * Resets shadow locale properties where the shadow base locale no longer exists.
     *
     * @param string[] $validLocales
     *
     * @return int Number of shadow references cleaned
     */
    public function cleanInvalidShadowReferences(NodeInterface $node, array $validLocales, bool $dryRun): int
    {
        $cleaned = 0;

        foreach ($validLocales as $locale) {
            $shadowOnKey = $this->languagePrefix . ':' . $locale . '-' . ShadowLocaleSubscriber::SHADOW_ENABLED_FIELD;
            $shadowBaseKey = $this->languagePrefix . ':' . $locale . '-' . ShadowLocaleSubscriber::SHADOW_LOCALE_FIELD;

            if (!$node->hasProperty($shadowOnKey) || !$node->hasProperty($shadowBaseKey)) {
                continue;
            }

            $shadowOnProperty = $node->getProperty($shadowOnKey);
            $shadowOn = $shadowOnProperty->getValue();
            if (!$shadowOn) {
                continue;
            }

            $shadowBaseProperty = $node->getProperty($shadowBaseKey);
            $shadowBase = $shadowBaseProperty->getValue();
            if (!\is_string($shadowBase)) {
                continue;
            }

            if (\in_array($shadowBase, $validLocales, true)) {
                continue;
            }

            $this->logger->writeln(\sprintf(
                '  Resetting invalid shadow reference: %s shadows %s (locale no longer exists)',
                $locale,
                $shadowBase,
            ));

            ++$cleaned;

            if (!$dryRun) {
                $shadowOnProperty->setValue(null);
                $shadowBaseProperty->setValue(null);
            }
        }

        return $cleaned;
    }

    /**
     * @return string[]
     */
    private function getLocales(NodeInterface $node): array
    {
        $locales = [];

        foreach ($node->getProperties() as $property) {
            \preg_match(
                \sprintf('/^%s:([a-zA-Z_]*?)-(template|title)/', $this->languagePrefix),
                $property->getName(),
                $matches,
            );

            if ($matches) {
                $locales[$matches[1]] = $matches[1];
            }
        }

        return \array_keys($locales);
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
