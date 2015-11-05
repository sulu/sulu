<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import;

use PHPCR\NodeInterface;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Import\Exception\WebspaceFormatImporterNotFoundException;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\PropertyEncoder;

class Webspace implements WebspaceInterface
{
    /**
     * @var DocumentManager
     */
    protected $documentManager;

    /**
     * @var DocumentInspector
     */
    protected $documentInspector;

    /**
     * @var DocumentRegistry
     */
    protected $documentRegistry;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var WebspaceFormatImportInterface[]
     */
    protected $fileParser = [];

    /**
     * @var ContentImportManagerInterface
     */
    protected $contentImportManager;

    /**
     * @var RlpStrategyInterface
     */
    protected $rlpStrategy;

    /**
     * {@inheritdoc}
     */
    public function add($service, $format)
    {
        $this->fileParser[$format] = $service;
    }

    /**
     * @param DocumentManager $documentManager
     * @param DocumentInspector $documentInspector
     * @param DocumentRegistry $documentRegistry
     * @param LegacyPropertyFactory $legacyPropertyFactory
     * @param RlpStrategyInterface $rlpStrategy
     * @param StructureManagerInterface $structureManager
     * @param ContentImportManagerInterface $contentImportManager
     */
    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        DocumentRegistry $documentRegistry,
        LegacyPropertyFactory $legacyPropertyFactory,
        RlpStrategyInterface $rlpStrategy,
        StructureManagerInterface $structureManager,
        ContentImportManagerInterface $contentImportManager
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->documentRegistry = $documentRegistry;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
        $this->rlpStrategy = $rlpStrategy;
        $this->structureManager = $structureManager;
        $this->contentImportManager = $contentImportManager;
    }

    /**
     * {@inheritdoc}
     */
    public function import(
        $webspaceKey,
        $locale,
        $filePath,
        $format = '1.2.xliff'
    ) {
        $parsedDataList = $this->getParser($format)->parse($filePath, $locale);
        $failedImports = [];

        foreach ($parsedDataList as $parsedData) {
            if (!$this->importDocument($parsedData, $format, $webspaceKey, $locale)) {
                $failedImports[] = $parsedData;
            }
        }
    }

    /**
     * @param array $parsedData
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool
     */
    protected function importDocument(array $parsedData, $format, $webspaceKey, $locale)
    {
        if (
            !isset($parsedData['uuid'])
            || !isset($parsedData['structureType'])
            || !isset($parsedData['data'])
        ) {
            return;
        }

        $uuid = $parsedData['uuid'];
        $structureType = $parsedData['structureType'];
        $data = $parsedData['data'];

        $document = $this->documentManager->find($uuid, $locale);

        if ($document->getWebspaceName() != $webspaceKey || !$document instanceof BasePageDocument) {
            return;
        }

        $this->setDocumentData($document, $structureType, $webspaceKey, $locale, $format, $data);
    }

    /**
     * @param BasePageDocument $document
     * @param string $structureType
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param array $data
     */
    protected function setDocumentData(BasePageDocument $document, $structureType, $webspaceKey, $locale, $format, $data)
    {
        $structure = $this->structureManager->getStructure($structureType);
        $properties = $structure->getProperties(true);
        $node = $this->documentRegistry->getNodeForDocument($document);

        foreach ($properties as $property) {
            $value = $this->getParser($format)->getPropertyData(
                $property->getName(),
                $data,
                $property->getContentTypeName()
            );

            if ($property->getContentTypeName() == 'resource_locator') {
                $parent = $document->getParent();
                if ($parent instanceof BasePageDocument) {
                    $parentPath = $parent->getResourceSegment();
                    $value = $this->generateUrl(
                        $structure->getPropertiesByTagName('sulu.rlp.part'),
                        $parentPath,
                        $webspaceKey,
                        $locale,
                        $format,
                        $data
                    );
                }
            }

            $this->importProperty($property, $node, $structure, $value, $webspaceKey, $locale, $format);
        }

        $extensions = $this->structureManager->getExtensions($structureType);

        foreach ($extensions as $extension) {
            $this->importExtension($extension, $node, $data, $webspaceKey, $locale, $format);
        }

        $this->saveNode($node);
    }

    /**
     * @param NodeInterface $node
     */
    protected function saveNode(NodeInterface $node)
    {
        // TODO
    }

    /**
     * @param $extension
     * @param $node
     * @param $data
     * @param $webspaceKey
     * @param $locale
     * @param $format
     */
    protected function importExtension($extension, $node, $data, $webspaceKey, $locale, $format)
    {
        // TODO
    }

    /**
     * @param PropertyInterface $property
     * @param NodeInterface $node
     * @param string $value
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     */
    protected function importProperty(
        PropertyInterface $property,
        NodeInterface $node,
        StructureInterface $structure,
        $value,
        $webspaceKey,
        $locale,
        $format
    ) {
        $contentType = $property->getContentTypeName();

        if ($this->contentImportManager->hasImport($contentType, $format)) {
            $translateProperty = $this->legacyPropertyFactory->createTranslatedProperty($property, $locale, $structure);
            $translateProperty->setValue($value);
            $this->contentImportManager->import($contentType, $node, $translateProperty, null, $webspaceKey, $locale);
        }
    }

    /**
     * @param $format
     *
     * @return WebspaceFormatImportInterface
     *
     * @throws WebspaceFormatImporterNotFoundException
     */
    protected function getParser($format)
    {
        if (!isset($this->fileParser[$format])) {
            throw new WebspaceFormatImporterNotFoundException($format);
        }

        return $this->fileParser[$format];
    }

    /**
     * @param PropertyInterface[] $properties
     * @param string $parentPath
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param array $data
     *
     * @return string
     */
    private function generateUrl($properties, $parentPath, $webspaceKey, $locale, $format, $data)
    {
        $rlpParts = [];

        foreach ($properties as $property) {
            $rlpParts[] = $this->getParser($format)->getPropertyData(
                $property->getName(),
                $data,
                $property->getContentTypeName()
            );
        }

        $title = trim(implode(' ', $rlpParts));

        return $this->rlpStrategy->generate($title, $parentPath, $webspaceKey, $locale);
    }
}
