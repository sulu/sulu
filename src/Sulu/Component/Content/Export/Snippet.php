<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Symfony\Component\Templating\EngineInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;

/**
 * Export Snippet by given locale to xliff file.
 */
class Snippet
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var SnippetRepository
     */
    private $snippetManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ContentExportManagerInterface
     */
    protected $contentExportManager;

    /**
     * @var String
     */
    protected $exportLocale;

    /**
     * @var String
     */
    protected $format;

    /**
     * @var string[]
     */
    protected $formatFilePaths;

    /**
     * Snippet constructor.
     *
     * @param EngineInterface $templating
     * @param SnippetRepository $snippetManager
     * @param DocumentManager $documentManager
     * @param DocumentInspector $documentInspector
     * @param array $formatFilePaths
     */
    public function __construct(
        EngineInterface $templating,
        SnippetRepository $snippetManager,
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        ContentExportManagerInterface $contentExportManager,
        $formatFilePaths
    )
    {
        $this->templating = $templating;
        $this->snippetManager = $snippetManager;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->contentExportManager = $contentExportManager;
        $this->formatFilePaths = $formatFilePaths;
    }

    /**
     * {@inheritdoc}
     */
    public function export(
        $locale,
        $output = null,
        $format = '1.2.xliff'
    )
    {
        if (!$locale) {
            throw new \Exception(sprintf('Invalid parameters for export "%s"', $locale));
        }

        $this->exportLocale = $locale;
        $this->output = $output;
        $this->format = $format;

        if (null === $this->output) {
            $this->output = new NullOutput();
        }

        return $this->templating->render(
            $this->getTemplate($format),
            $this->getExportData()
        );
    }

    /**
     * Returns export template for given format like XLIFF1.2.
     *
     * @param $format
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getTemplate($format)
    {
        if (!isset($this->formatFilePaths[$format])) {
            throw new \Exception(sprintf('No format "%s" configured for webspace export', $format));
        }

        $templatePath = $this->formatFilePaths[$format];

        if (!$this->templating->exists($templatePath)) {
            throw new \Exception(sprintf('No template file "%s" found for webspace export', $format));
        }

        return $templatePath;
    }

    protected function getExportData()
    {
        $snippets = $this->getSnippets();
        $snippetsData = [];

        $progress = new ProgressBar($this->output, count($snippets));
        $progress->start();

        /**
         * @var SnippetDocument
         */
        foreach ($snippets as $snippet) {
            $contentData = $this->getContentData($snippet, $this->exportLocale);

            $snippetsData[] = [
                'uuid' => $snippet->getUuid(),
                'locale' => $snippet->getLocale(),
                'content' => $contentData,
            ];

            $progress->advance();
        }

        $progress->finish();

        return [
            'locale' => $this->exportLocale,
            'format' => $this->format,
            'snippetData' => $snippetsData,
        ];
    }

    /**
     * Returns a array of the given content data of the document.
     *
     * @param BasePageDocument $document
     * @param $locale
     *
     * @return array
     */
    protected function getContentData(SnippetDocument $document, $locale)
    {
        /** @var BasePageDocument $loadedDocument */
        $loadedDocument = $this->documentManager->find($document->getUuid(), $locale);

        /** @var \Sulu\Component\Content\Metadata\StructureMetadata $metaData */
        $metaData = $this->documentInspector->getStructureMetadata($document);

        $propertyValues = $loadedDocument->getStructure()->toArray();
        $properties = $metaData->getProperties();

        $contentData = $this->getPropertiesContentData($properties, $propertyValues);

        return $contentData;
    }

    /**
     * Returns the Content as a flat array.
     *
     * @param PropertyMetadata[] $properties
     * @param $propertyValues
     * @param $format
     *
     * @return array
     */
    protected function getPropertiesContentData($properties, $propertyValues)
    {
        $contentData = [];

        foreach ($properties as $property) {
            if ($this->contentExportManager->hasExport($property->getType(), $this->format)) {
                if (!isset($propertyValues[$property->getName()])) {
                    continue;
                }

                $propertyValue = $propertyValues[$property->getName()];

                if ($property instanceof BlockMetadata) {
                    $data = $this->getBlockPropertyData($property, $propertyValue);
                } else {
                    $data = $this->getPropertyData($property, $propertyValue);
                }

                $contentData[$property->getName()] = $data;
            }
        }

        return $contentData;
    }

    /**
     * Creates and Returns a property-array for content-type Block.
     *
     * @param BlockMetadata $property
     * @param PropertyValue $propertyValue
     * @param $format
     *
     * @return array
     */
    protected function getBlockPropertyData(BlockMetadata $property, $propertyValue)
    {
        $children = [];

        $blockDataList = $this->contentExportManager->export($property->getType(), $propertyValue);

        foreach ($blockDataList as $blockData) {
            $blockType = $blockData['type'];

            $block = $this->getPropertiesContentData(
                $property->getComponentByName($blockType)->getChildren(),
                $blockData,
                $this->format
            );

            $block['type'] = $this->createProperty(
                'type',
                $blockType,
                $this->contentExportManager->getOptions($property->getType(), $this->format),
                $property->getType() . '_type'
            );

            $children[] = $block;
        }

        return $this->createProperty(
            $property->getName(),
            null,
            $this->contentExportManager->getOptions($property->getType(), $this->format),
            $property->getType(),
            $children
        );
    }

    /**
     * Creates and returns a property-array.
     *
     * @param PropertyMetadata $property
     * @param PropertyValue $propertyValue
     *
     * @return array
     */
    protected function getPropertyData(PropertyMetadata $property, $propertyValue)
    {
        return $this->createProperty(
            $property->getName(),
            $this->contentExportManager->export($property->getType(), $propertyValue),
            $this->contentExportManager->getOptions($property->getType(), $this->format),
            $property->getType()
        );
    }

    /**
     * Returns a array with the given value (name, value and options).
     *
     * @param $name
     * @param $value
     * @param array $options
     * @param string $type
     * @param array $children
     *
     * @return array
     */
    protected function createProperty($name, $value = null, $options = [], $type = '', $children = null)
    {
        $property = [
            'name' => $name,
            'type' => $type,
            'options' => $options,
        ];

        if ($children) {
            $property['children'] = $children;
        } else {
            $property['value'] = $value;
        }

        return $property;
    }

    protected function getSnippets()
    {
        $this->output->writeln('<info>Loading Data…</info>');

        return $this->snippetManager->getSnippets($this->exportLocale);
    }
}