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

use Ds\Set;
use PHPCR\PathNotFoundException;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ComponentMetadata;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\PHPCR\PropertyParser\PropertyParserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class PhpCRCleanerCommand extends Command
{
    protected static $defaultName = 'sulu:document:clean';

    private OutputInterface $output;

    private Set $nodesToDelete;

    public function __construct(
        private PropertyParserInterface $propertyParser,
        private SessionInterface $session,
        private StructureMetadataFactoryInterface $structureMetaDataFactory
    ) {
        parent::__construct();
        $this->nodesToDelete = new Set();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $rows = $queryManager->createQuery('SELECT * FROM [nt:unstructured]', 'JCR-SQL2')->execute();

        $i = 0;
        foreach ($rows->getNodes() as $node) {
            $this->nodesToDelete->clear();
            $propertyData = $this->propertyParser->parse($node->getPropertiesValues('i18n:*'));

            foreach ($propertyData as $locale => $contentData) {
                $output->writeln('======' . $locale . '======');

                /** @var string $template */
                $template = $contentData['template']->getValue();

                try {
                    $metaData = $this->structureMetaDataFactory->getStructureMetadata('page', $template);
                } catch (\Throwable) {
                    $metaData = null;
                }

                if (null === $metaData) {
                    $output->writeln(\sprintf(
                        '[Skipping] Unable to load metaData for structure with type "page" and "%s"',
                        $template
                    ));
                    continue;
                }

                $this->migrate('', $contentData, $metaData, 0);
            }

            $paths = $this->propertyParser->keyIterator($this->nodesToDelete->toArray());
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

    private function ddump(string $message, int $depth, int $verbosity = OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln(\str_repeat('  ', $depth) . $message, $verbosity);
    }

    /**
     * @param array<mixed> $data
     */
    public function migrate(string|int $key, $data, object $metaData, int $depth): void
    {
        // If we are at the top of the metadata run we don't want to mess with that as it contains extra properties like seo and other extension data
        if ($metaData instanceof StructureMetadata) {
            foreach ($metaData->getProperties() as $subKey => $value) {
                $this->ddump('Iterating over: ' . $subKey, $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $this->migrate($subKey, $data[$subKey] ?? null, $value, $depth);
            }

            return;
        }

        if ($metaData instanceof BlockMetadata) {
            if (null === $data) {
                Assert::eq(0, $depth, 'Empty arrays are only allowed at the root');
                $this->ddump('Empty page? ' . $metaData->getName(), $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);
            } else {
                $this->handleBlockMetadata($data, $metaData, $depth);
            }
        } elseif ($metaData instanceof ComponentMetadata) {
            $this->handleComponentMetadata($data, $metaData, $depth);
        } elseif ($metaData instanceof PropertyMetadata) {
            Assert::eq($metaData->getName(), $key);
            $this->ddump('Property: ' . $metaData->getName(), $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);
        } else {
            Assert::false(true, 'Unable to handle meta data of class: ' . \get_class($metaData));
        }
    }

    private function handleBlockMetadata(array $data, BlockMetadata $metadata, int $depth): void
    {
        $this->ddump('============ ' . $metadata->getName() . ' =============', $depth, OutputInterface::VERBOSITY_QUIET);

        if (!\array_key_exists('length', $data)) {
            \trigger_error('Expected length to exists: ' . \join(',', \array_keys($data)), \E_USER_WARNING);

            return;

            return;
        }

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
                    $this->nodesToDelete->add($value);
                }
            } else {
                $this->nodesToDelete->add($value);
            }
        }
    }

    private function handleComponentMetadata(array $data, ComponentMetadata $metaData, int $depth): void
    {
        $this->ddump('Found component with name: ' . $metaData->getName(), $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);
        $unusedPropeties = new Set(\array_keys($data));
        $unusedPropeties->remove('type');
        $unusedPropeties->remove('settings');

        foreach ($metaData->getChildren() as $propertyName => $child) {
            if (!\array_key_exists($propertyName, $data)) {
                $this->ddump('Found unconfigured property: ' . $propertyName, $depth, OutputInterface::VERBOSITY_VERY_VERBOSE);

                continue;
            }

            $this->migrate($propertyName, $data[$propertyName], $child, $depth + 1);
            $unusedPropeties->remove($propertyName);
        }

        if (\count($unusedPropeties) > 0) {
            $this->ddump(
                'Found dead propety: ' . \join(', ', $unusedPropeties->toArray()),
                $depth,
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );
        }

        $this->nodesToDelete->add($unusedPropeties->map(fn (string $x) => $data[$x])->toArray());
    }
}
