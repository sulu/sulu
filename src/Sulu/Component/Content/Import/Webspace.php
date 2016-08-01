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
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Import\Exception\WebspaceFormatImporterNotFoundException;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentRegistry;

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
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    static protected $excludedSettings = [
        'title',
        'locale',
        'webspaceName',
        'structureType',
        'originalLocale',
    ];

    /**
     * @var array
     */
    static protected $settingsToArray = [
        'permissions',
        'navigationContexts',
    ];

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
     * @param ExtensionManagerInterface $extensionManager
     * @param ContentImportManagerInterface $contentImportManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        DocumentRegistry $documentRegistry,
        LegacyPropertyFactory $legacyPropertyFactory,
        RlpStrategyInterface $rlpStrategy,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ContentImportManagerInterface $contentImportManager,
        LoggerInterface $logger
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->documentRegistry = $documentRegistry;
        $this->legacyPropertyFactory = $legacyPropertyFactory;
        $this->rlpStrategy = $rlpStrategy;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->contentImportManager = $contentImportManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function import(
        $webspaceKey,
        $locale,
        $filePath,
        $format = '1.2.xliff',
        $uuid = null,
        $overrideSettings = false
    ) {
        $parsedDataList = $this->getParser($format)->parse($filePath, $locale);
        $failedImports = [];
        $importedCounter = 0;
        $successCounter = 0;

        foreach ($parsedDataList as $parsedData) {
            // filter for specific uuid
            if (!$uuid || isset($parsedData['uuid']) && $parsedData['uuid'] == $uuid) {
                ++$importedCounter;

                if (!$this->importDocument($parsedData, $format, $webspaceKey, $locale, $overrideSettings)) {
                    $failedImports[] = $parsedData;
                } else {
                    ++$successCounter;
                }

                $this->logger->info(sprintf('Document %s/%s', $importedCounter, $uuid ? 1 : count($parsedDataList)));
            }
        }

        return [
            $importedCounter,
            count($failedImports),
            $successCounter,
            $failedImports,
        ];
    }

    /**
     * @param array $parsedData
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool
     */
    protected function importDocument(array $parsedData, $format, $webspaceKey, $locale, $overrideSettings)
    {
        $uuid = null;

        try {
            if (
                !isset($parsedData['uuid'])
                || !isset($parsedData['structureType'])
                || !isset($parsedData['data'])
            ) {
                throw new \Exception('uuid, structureType or data for import not found.');
            }

            $uuid = $parsedData['uuid'];
            $structureType = $parsedData['structureType'];
            $data = $parsedData['data'];
            $documentType = Structure::TYPE_PAGE;

            if ($this->getParser($format)->getPropertyData('url', $data) === '/') {
                $documentType = 'home'; // TODO no constant
            }

            /** @var BasePageDocument $document */
            $document = $this->documentManager->find(
                $uuid,
                $locale,
                [
                    'type' => $documentType,
                    'load_ghost_content' => false,
                ]
            );

            $document->setStructureType($structureType);

            if ($document->getWebspaceName() != $webspaceKey) {
                throw new \Exception(
                    sprintf('Document(%s) is part of another webspace: "%s"', $uuid, $document->getWebspaceName())
                );
            }

            if (!$document instanceof BasePageDocument) {
                throw new \Exception(
                    sprintf('Document(%s) is not an instanecof BasePageDocument', $uuid)
                );
            }

            $this->setDocumentData($document, $structureType, $webspaceKey, $locale, $format, $data);
            $this->setDocumentSettings($document, $structureType, $webspaceKey, $locale, $format, $data, $overrideSettings);

            // save document
            $this->documentManager->persist($document, $locale);
            $this->documentManager->publish($document, $locale);
            $this->documentManager->flush();
            $this->documentRegistry->clear(); // FIXME else it failed on multiple page import

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '<info>%s</info>%s: <error>%s</error>%s',
                    $uuid,
                    PHP_EOL . get_class($e),
                    $e->getMessage(),
                    PHP_EOL . $e->getTraceAsString()
                )
            );

            $this->documentManager->flush();
            $this->documentManager->clear();
        }

        return false;
    }

    /**
     * @param BasePageDocument $document
     * @param string $structureType
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param array $data
     */
    protected function setDocumentData(
        BasePageDocument $document,
        $structureType,
        $webspaceKey,
        $locale,
        $format,
        $data
    ) {
        $structure = $this->structureManager->getStructure($structureType);
        $properties = $structure->getProperties(true);
        $node = $this->documentRegistry->getNodeForDocument($document);
        $node->setProperty(sprintf('i18n:%s-template', $locale), $structureType);
        $state = $this->getParser($format)->getPropertyData('state', $data, null, null, 2);
        $node->setProperty(sprintf('i18n:%s-state', $locale), $state);

        // import all content data
        foreach ($properties as $property) {
            $value = $this->getParser($format)->getPropertyData(
                $property->getName(),
                $data,
                $property->getContentTypeName()
            );

            // don't generate a new url when one exists
            $doImport = true;
            if ($property->getContentTypeName() == 'resource_locator') {
                if (!$document->getResourceSegment()) {
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
                } else {
                    $doImport = false;
                }
            }

            // import property data
            if ($doImport) {
                $this->importProperty($property, $node, $structure, $value, $webspaceKey, $locale, $format);
            }
        }

        // import extensions
        $extensions = $this->extensionManager->getExtensions($structureType);

        foreach ($extensions as $key => $extension) {
            $this->importExtension($extension, $key, $node, $data, $webspaceKey, $locale, $format);
        }

        // set required data
        $document->setTitle($this->getParser($format)->getPropertyData('title', $data));
    }

    /**
     * @param BasePageDocument $document
     * @param string $structureType
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param array $data
     */
    protected function setDocumentSettings($document, $structureType, $webspaceKey, $locale, $format, $data, $overrideSettings)
    {
        if ('true' !== $overrideSettings) {
            return;
        }

        foreach($data as $key => $property){
            $setter = 'set' . ucfirst($key);

            if (in_array($key, self::$excludedSettings) || !method_exists($document, $setter)) {
                continue;
            }

            $value = $this->getParser($format)->getPropertyData(
                $key,
                $data
            );

            if (in_array($key, self::$settingsToArray)) {
                $value = json_decode($value);
            }

            $document->$setter($value);

        }
    }

    /**
     * @param ExportExtensionInterface $extension
     * @param string $extensionKey
     * @param NodeInterface $node
     * @param array $data
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     */
    protected function importExtension(
        ExportExtensionInterface $extension,
        $extensionKey,
        NodeInterface $node,
        $data,
        $webspaceKey,
        $locale,
        $format
    ) {
        $extensionData = [];

        foreach ($extension->getImportPropertyNames() as $propertyName) {
            $value = $this->getParser($format)->getPropertyData(
                $propertyName,
                $data,
                null,
                $extensionKey
            );

            $extensionData[$propertyName] = $value;
        }

        $extension->import($node, $extensionData, $webspaceKey, $locale, $format);
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
