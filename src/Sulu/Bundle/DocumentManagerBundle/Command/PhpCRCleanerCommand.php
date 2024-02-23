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
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\Factory\Exception\DocumentTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\Exception\StructureTypeNotFoundException;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\PHPCR\PropertyParser\Property;
use Sulu\Component\PHPCR\PropertyParser\PropertyParserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class PhpCRCleanerCommand extends Command
{
    protected static $defaultName = 'sulu:document:clean';

    private OutputInterface $output;

    /** @var array<mixed> */
    private array $nodesToDelete = [];

    private array $aliasMapping = [];

    public function __construct(
        private PropertyParserInterface $propertyParser,
        private SessionInterface $session,
        private StructureMetadataFactoryInterface $structureMetaDataFactory,
        private MetadataFactoryInterface $documentMetaDataFactory,
        private array $mapping,
    ) {
        parent::__construct();

        foreach ($this->mapping as $item) {
            $this->aliasMapping[$item['phpcr_type']] = $item['alias'];
        }
    }

    protected function getAliasForNode(NodeInterface $node): ?string
    {
        foreach ($node->getMixinNodeTypes() as $mixinNodeType) {
            if (isset($this->aliasMapping[$mixinNodeType->getName()])) {
                return $this->aliasMapping[$mixinNodeType->getName()];
            }
        }

        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $rows = $queryManager->createQuery('SELECT * FROM [nt:unstructured]', 'JCR-SQL2')->execute();

        $i = 0;
        foreach ($rows->getNodes() as $node) {
            $alias = $this->getAliasForNode($node);
            if (null === $alias || !$this->structureMetaDataFactory->hasStructuresFor($alias)) {
                continue;
            }

            $documentMetadata = $this->documentMetaDataFactory->getMetadataForAlias($alias);
            $fieldMappings = $documentMetadata->getFieldMappings(); // check if that is enough to identify root level properties.

            $propertyData = $this->propertyParser->parse($node->getPropertiesValues('i18n:*'));

            foreach ($propertyData as $locale => $contentData) {
                /** @var array<Property> $contentData */
                $output->writeln('======' . $locale . '======');

                /** @var string|null $template */
                $template = $contentData['template']?->getValue();
                if (null === $template) {
                    continue;
                }

                try {
                    $metaData = $this->structureMetaDataFactory->getStructureMetadata($alias, $template);
                } catch (StructureTypeNotFoundException|DocumentTypeNotFoundException) {
                    $metaData = null;
                }

                if (null === $metaData) {
                    $output->writeln(\sprintf(
                        '[Skipping] Unable to load metaData for structure with type "page" and "%s"',
                        $template,
                    ));
                    continue;
                }

                $this->migrateStructure($contentData, $metaData, 0);
            }

            $paths = $this->propertyParser->keyIterator($this->getNodesToDeleteAndClear());
            foreach ($paths as $propertyPath) {
                try {
                    $node->getProperty($propertyPath)->remove();
                    ++$i;
                } catch (PathNotFoundException) {
                    continue;
                }
            }
        }
        $this->session->save();

        $this->output->writeln('Deleted properties: ' . $i);

        return 0;
    }

    /**
     * @param 16|32|64|128|256 $verbosity
     */
    private function depthDump(string $message, int $depth, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln(\str_repeat('  ', $depth) . $message, $verbosity);
    }

    /**
     * @param array<mixed>|null $data
     */
    public function migrateStructure(array $data, object $metaData): void
    {
        foreach ($metaData->getProperties() as $subKey => $value) {
            $this->depthDump('Iterating over: ' . $subKey, 0, OutputInterface::VERBOSITY_VERY_VERBOSE);

            /** @var array<mixed>|null $subValue */
            $subValue = $data[$subKey] ?? null;
            $this->migrate($subValue, $value, 0);
        }
    }

    public function migrate(mixed $data, ItemMetadata $metaData, int $depth): void
    {
        if ($metaData instanceof BlockMetadata) {
            if (null === $data) {
                Assert::eq(0, $depth, 'Empty arrays are only allowed at the root');
                $this->depthDump('Empty page? ' . $metaData->getName(), $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $this->handleBlockMetadata($data, $metaData, $depth);
            }
        } elseif ($metaData instanceof ComponentMetadata) {
            if (null === $data) {
                Assert::eq(0, $depth, 'Empty arrays are only allowed at the root');
                $this->depthDump('Empty page? ' . $metaData->getName(), $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $this->handleComponentMetadata($data, $metaData, $depth);
            }
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function handleBlockMetadata(array $data, BlockMetadata $metadata, int $depth): void
    {
        $this->depthDump('============ ' . $metadata->getName() . ' =============', $depth, OutputInterface::VERBOSITY_QUIET);

        if (!\array_key_exists('length', $data)) {
            \trigger_error('Expected length to exist: ' . \implode(',', \array_keys($data)), \E_USER_WARNING);

            return;
        }

        /* Handle unused blocks inside the metadata. In the PHPCR implementation Sulu saves arrays as data + length.
           This means deleting a block is trivially easy because you just have to decrease the length property.
           However this also means that if you have data whos index is greater or equal to the length then it's
           considered deleted. Here we actually delete those kinds of properties. */
        $length = $data['length']->value;
        Assert::greaterThan($length, 0);

        foreach ($data as $index => $value) {
            if ('length' === $index) {
                continue;
            }

            if ($index < $length) {
                $type = $value['type']->value;
                $component = $metadata->getComponentByName($type);
                if (null !== $component) {
                    $this->handleComponentMetadata($value, $component, $depth + 1);
                } else {
                    $this->nodesToDelete[] = $value;
                }
            } else {
                $this->nodesToDelete[] = $value;
            }
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function handleComponentMetadata(array $data, ItemMetadata $metaData, int $depth): void
    {
        $this->depthDump('Found component with name: ' . $metaData->getName(), $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);

        // Remove 'type' and 'settings' from the unused properties because they are always allowed
        $unusedProperties = \array_filter(\array_keys($data), fn (string $x) => !\in_array($x, ['type', 'settings'], true));

        foreach ($metaData->getChildren() as $propertyName => $child) {
            if (!\array_key_exists($propertyName, $data)) {
                $this->depthDump('Found unconfigured property: ' . $propertyName, $depth, OutputInterface::VERBOSITY_VERBOSE);

                continue;
            }

            /** @var array<mixed>|null $data */
            $data = $data[$propertyName];
            $this->migrate($data, $child, $depth + 1);

            $position = \array_search($propertyName, $unusedProperties, true);
            if (false !== $position) {
                unset($unusedProperties[$position]);
            }
        }

        if (\count($unusedProperties) > 0) {
            $this->depthDump(
                'Found dead property: ' . \implode(', ', $unusedProperties),
                $depth,
                OutputInterface::VERBOSITY_VERY_VERBOSE,
            );
        }

        $this->nodesToDelete = [
            ...$this->nodesToDelete,
            ...\array_map(fn (string $x) => $data[$x], $unusedProperties),
        ];
    }

    /**
     * @return array<mixed>
     */
    private function getNodesToDeleteAndClear(): array
    {
        $toDelete = \array_unique($this->nodesToDelete);
        $this->nodesToDelete = [];

        return $toDelete;
    }
}
