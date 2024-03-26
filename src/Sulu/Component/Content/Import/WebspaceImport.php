<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Import;

use PHPCR\NodeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\LegacyPropertyFactory;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Exception\ResourceLocatorGeneratorException;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\Import\Exception\FormatImporterNotFoundException;
use Sulu\Component\Import\Format\FormatImportInterface;
use Sulu\Component\Import\Import;
use Sulu\Component\Import\Manager\ImportManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Import Content by given xliff file from Export.
 */
class WebspaceImport extends Import implements WebspaceImportInterface
{
    /**
     * @var DocumentManagerInterface
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
     * @var ResourceLocatorStrategyInterface
     */
    protected $rlpStrategy;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected static $excludedSettings = [
        'title',
        'locale',
        'webspaceName',
        'structureType',
        'originalLocale',
        'url',
        'resourceSegment',
    ];

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector,
        DocumentRegistry $documentRegistry,
        LegacyPropertyFactory $legacyPropertyFactory,
        ResourceLocatorStrategyInterface $rlpStrategy,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ImportManagerInterface $importManager,
        FormatImportInterface $xliff12,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($importManager, $legacyPropertyFactory, ['1.2.xliff' => $xliff12]);

        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->documentRegistry = $documentRegistry;
        $this->rlpStrategy = $rlpStrategy;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->logger = $logger ?: new NullLogger();
    }

    public function import(
        $webspaceKey,
        $locale,
        $filePath,
        $output = null,
        $format = '1.2.xliff',
        $uuid = null,
        $overrideSettings = false,
        $exportSuluVersion = '1.3'
    ) {
        $parsedDataList = $this->getParser($format)->parse($filePath, $locale);
        $failedImports = [];
        $importedCounter = 0;
        $successCounter = 0;

        if (null === $output) {
            $output = new NullOutput();
        }

        $progress = new ProgressBar($output, \count($parsedDataList));
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progress->start();

        foreach ($parsedDataList as $parsedData) {
            // mapping data
            if ('1.2' === $exportSuluVersion) {
                $parsedData['structureType'] = $parsedData['data']['template']['value'];
            }

            // filter for specific uuid
            // !$uuid || isset($parsedData['uuid']) && $parsedData['uuid'] == $uuid
            if ($uuid && (!isset($parsedData['uuid']) || $uuid !== $parsedData['uuid'])) {
                $progress->advance();

                continue;
            }

            ++$importedCounter;

            if (!$this->importDocument($parsedData, $format, $webspaceKey, $locale, $overrideSettings)) {
                $failedImports[] = $parsedData;
            } else {
                ++$successCounter;
            }

            $this->logger->info(\sprintf('Document %s/%s', $importedCounter, $uuid ? 1 : \count($parsedDataList)));

            $progress->advance();
        }

        $progress->finish();

        $return = new \stdClass();
        $return->count = $importedCounter;
        $return->fails = \count($failedImports);
        $return->successes = $successCounter;
        $return->failed = $failedImports;
        $return->exceptionStore = $this->exceptionStore;

        return $return;
    }

    /**
     * Import document by locale into given webspace.
     *
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return bool
     */
    protected function importDocument(array $parsedData, $format, $webspaceKey, $locale, $overrideSettings)
    {
        $uuid = null;

        try {
            if (!isset($parsedData['uuid']) || !isset($parsedData['structureType']) || !isset($parsedData['data'])) {
                $this->addException('uuid, structureType or data for import not found.', 'ignore');

                throw new \Exception('uuid, structureType or data for import not found.');
            }

            $uuid = $parsedData['uuid'];
            $structureType = $parsedData['structureType'];
            $data = $parsedData['data'];
            $documentType = Structure::TYPE_PAGE;

            if ('/' === $this->getParser($format)->getPropertyData('url', $data)) {
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
                $this->addException(\sprintf('Document(%s) is part of another webspace: "%s"', $uuid, $document->getWebspaceName()), 'ignore');

                throw new \Exception(
                    \sprintf('Document(%s) is part of another webspace: "%s"', $uuid, $document->getWebspaceName())
                );
            }

            if (!$document instanceof BasePageDocument) {
                throw new \Exception(
                    \sprintf('Document(%s) is not an instanecof BasePageDocument', $uuid)
                );
            }

            if (!$this->setDocumentData($document, $structureType, $webspaceKey, $locale, $format, $data)) {
                return false;
            }
            $this->setDocumentSettings($document, $format, $data, $overrideSettings);

            // save document
            $this->documentManager->persist($document, $locale);

            if (WorkflowStage::PUBLISHED === ((int) $this->getParser($format)->getPropertyData('workflowStage', $data))) {
                $this->documentManager->publish($document, $locale);
            }

            $this->documentManager->flush();
            $this->documentRegistry->clear(); // FIXME else it failed on multiple page import

            return true;
        } catch (\Exception $e) {
            if ($e instanceof DocumentManagerException) {
                return;
            }

            $this->logger->error(
                \sprintf(
                    '<info>%s</info>%s: <error>%s</error>%s',
                    $uuid,
                    \PHP_EOL . \get_class($e),
                    $e->getMessage(),
                    \PHP_EOL . $e->getTraceAsString()
                )
            );

            $this->documentManager->flush();
            $this->documentManager->clear();
        }

        return false;
    }

    /**
     * Loops all documents and imports all properties of the documents.
     *
     * @param string $structureType
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param array $data
     *
     * @return bool
     *
     * @throws FormatImporterNotFoundException
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
        $node->setProperty(\sprintf('i18n:%s-template', $locale), $structureType);
        $state = $this->getParser($format)->getPropertyData('state', $data, null, null, 2);
        $node->setProperty(\sprintf('i18n:%s-state', $locale), $state);

        if ('' === $this->getParser($format)->getPropertyData('title', $data)) {
            $this->addException(\sprintf('Document(%s) has not set any title', $document->getUuid()), 'ignore');

            return false;
        }

        // import all content data
        foreach ($properties as $property) {
            $value = $this->getParser($format)->getPropertyData(
                $property->getName(),
                $data,
                $property->getContentTypeName()
            );

            // don't generate a new url when one exists
            $doImport = true;
            if ('resource_locator' == $property->getContentTypeName()) {
                $doImport = false;

                if (!$document->getResourceSegment()) {
                    $doImport = true;
                    $parent = $document->getParent();

                    if ($parent instanceof BasePageDocument) {
                        $parentUuid = $parent->getUuid();

                        try {
                            $resourceSegment = $this->generateUrl(
                                $structure->getPropertiesByTagName('sulu.rlp.part'),
                                $parentUuid,
                                $webspaceKey,
                                $locale,
                                $format,
                                $data
                            );
                        } catch (ResourceLocatorGeneratorException $exception) {
                            $resourceSegment = null;
                        }

                        if (!$resourceSegment || '/' === $resourceSegment) {
                            if (!$value) {
                                $this->addException(
                                    \sprintf('Document(%s) needs an resource locator (%s) because no url could be generated', $document->getUuid(), $property->getName()),
                                    'ignore'
                                );

                                return false;
                            }

                            $document->setResourceSegment($value);
                        } else {
                            $document->setResourceSegment($resourceSegment);
                        }
                    }
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

        return true;
    }

    /**
     * Set all Settings for the given documents and import them.
     * Import property -o must be set to true.
     *
     * @param string $format
     * @param array $data
     * @param string $overrideSettings
     *
     * @throws DocumentManagerException
     * @throws FormatImporterNotFoundException
     */
    protected function setDocumentSettings(
        BasePageDocument $document,
        $format,
        $data,
        $overrideSettings
    ) {
        if ('true' !== $overrideSettings) {
            return;
        }

        foreach ($data as $key => $property) {
            $setter = 'set' . \ucfirst($key);

            if (\in_array($key, self::$excludedSettings) || !\method_exists($document, $setter)) {
                continue;
            }

            $value = $this->getParser($format)->getPropertyData(
                $key,
                $data
            );

            $document->$setter($this->getSetterValue($key, $value));
        }
    }

    /**
     * Prepare the settings value for the respective setter.
     *
     * @param string $key
     * @param mixed|object $value
     *
     * @return null|mixed|object
     *
     * @throws DocumentManagerException
     */
    protected function getSetterValue($key, $value)
    {
        if (empty($value)) {
            return;
        }

        $value = match ($key) {
            'redirectTarget' => $this->documentManager->find($value),
            'permissions' => \json_decode($value, true),
            'navigationContexts' => \json_decode($value),
            default => $value,
        };

        return $value;
    }

    /**
     * Importing the Extensions like SEO- and Excerption-Tab.
     *
     * @param string $extensionKey
     * @param array $data
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     *
     * @throws FormatImporterNotFoundException
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
     * Generates a url by given strategy and property.
     *
     * @param PropertyInterface[] $properties
     * @param string $parentUuid
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param array $data
     *
     * @return string
     *
     * @throws FormatImporterNotFoundException
     */
    private function generateUrl($properties, $parentUuid, $webspaceKey, $locale, $format, $data)
    {
        $rlpParts = [];

        foreach ($properties as $property) {
            $rlpParts[] = $this->getParser($format)->getPropertyData(
                $property->getName(),
                $data,
                $property->getContentTypeName()
            );
        }

        $title = \trim(\implode(' ', $rlpParts));

        return $this->rlpStrategy->generate($title, $parentUuid, $webspaceKey, $locale);
    }
}
