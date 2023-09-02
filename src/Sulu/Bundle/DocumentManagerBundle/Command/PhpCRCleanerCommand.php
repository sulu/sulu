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
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\Loader\StructureXmlLoader;
use Sulu\Component\PHPCR\PropertyParser\PropertyParserInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PhpCRCleanerCommand extends Command
{
    protected static $defaultName = 'sulu:document:clean';

    private OutputInterface $output;

    public function __construct(
        private PropertyParserInterface $phpcrPropertyParser,
        private SessionInterface $session,
        private StructureMetadataFactoryInterface $structureMetaDataFactory
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $rows = $queryManager->createQuery('SELECT * FROM [nt:unstructured]', 'JCR-SQL2')->execute();

        $structureTypeMappings = [
            1 => 'home',
        ];

        foreach ($rows->getNodes() as $node) {
            $propertyData = $this->phpcrPropertyParser->parse($node->getPropertiesValues('i18n:*'));
            foreach($propertyData as $locale => $contentData) {

            $output->writeln('======'.$locale.'======');

                /** @var string $template */
                $template = $contentData['template']->getValue();
                $structureType = $contentData['nodeType']->getValue();
                assert($structureType === 1);

                $metaData = $this->structureMetaDataFactory
                ->getStructureMetadata($structureTypeMappings[$structureType], $template);

                if ($metaData === null) {
                    $output->writeln(sprintf(
                        '[Skipping] Unable to load metaData for structure with type "%s" and "%s"',
                        $structureTypeMappings[$structureType],
                        $template
                    ));
                    continue;
                }

                $this->recursiveRemove($contentData, $node, $metaData->getProperties(), 0);
            }

            $this->session->save();
        }
        $this->session->save();

        return 0;
    }

    /**
     * Iterates through the tree-like structure that the properties are now and removes excessive nodes.
     * @param array<int,mixed> $propertyData
     * @param array<int,mixed> $metaData
     */
    private function recursiveRemove(array $propertyData, NodeInterface $node, array $metaData, int $depth): void
    {
        foreach ($propertyData as $key => $value) {
            if ($depth === 0 && in_array($key, StructureXmlLoader::RESERVED_KEYS)) {
                continue;
            }

            if (\is_array($value)) {
                if (!array_key_exists($key, $metaData)) {
                    $this->output->writeln('Skipping property: '. $key .' at depth '.$depth);
                    continue;
                }
                $this->recursiveRemove($value, $node, $metaData[$key]->getChildren(), $depth + 1);
            } else {
                dump(get_class($metaData[$key]), (string) $value);
            }
        }
    }

    /**
     * Iterates over all keys under a given node and removes them from phpcr.
     * @param array<int,mixed> $data
     */
    private function deleteProperties(array $data, NodeInterface $node): void
    {
        foreach ($this->phpcrPropertyParser->keyIterator($data) as $key) {
            try {
                $node->getProperty($key)->remove();
            } catch (PathNotFoundException) {
            }
        }
    }
}
