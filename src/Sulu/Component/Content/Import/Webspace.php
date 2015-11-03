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

use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Import\Exception\WebspaceFormatImporterNotFoundException;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\DocumentManager;

class Webspace implements WebspaceInterface
{
    /**
     * @var WebspaceFormatImportInterface[]
     */
    protected $fileParser = [];

    /**
     * {@inheritdoc}
     */
    public function add($service, $format)
    {
        $this->fileParser[$format] = $service;
    }

    public function __construct(
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        StructureManagerInterface $structureManager
    ) {
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->structureManager = $structureManager;
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
        $parsedDataList = $this->getParser($format)->import($filePath, $locale);
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
        if (!isset($parsedData['uuid'])) {
            return;
        }

        if (!isset($parsedData['structureType'])) {
            return;
        }

        if (!isset($parsedData['data'])) {
            return;
        }

        $uuid = $parsedData['uuid'];
        $structureType = $parsedData['structureType'];
        $data = $parsedData['data'];

        $document = $this->loadDocument($uuid, $webspaceKey, $locale);

        $this->setDocumentData($document, $structureType, $format, $data);
    }

    /**
     * @param $document
     * @param string $structureType
     * @param string $format
     * @param array $data
     */
    protected function setDocumentData($document, $structureType, $format, $data)
    {
        // $this->getParser($format)->getPropertyData($name, $data);
    }

    /**
     * @param $uuid
     * @param $webspaceKey
     * @param $locale
     *
     * @return object
     */
    protected function loadDocument($uuid, $webspaceKey, $locale)
    {
        return $this->documentManager->find($uuid, $locale);
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
}
