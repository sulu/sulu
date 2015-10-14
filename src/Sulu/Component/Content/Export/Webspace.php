<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Export;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Templating\EngineInterface;

class Webspace implements WebspaceInterface
{
    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var EngineInterface
     */
    protected $documentManager;

    /**
     * @var EngineInterface
     */
    protected $documentInspector;

    /**
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var string[]
     */
    protected $formatFilePaths;

    /**
     * @param EngineInterface $templating
     * @param DocumentManager $documentManager
     * @param DocumentInspector $documentInspector
     * @param StructureManagerInterface $structureManager
     * @param array $formatFilePaths
     */
    public function __construct(
        EngineInterface $templating,
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        StructureManagerInterface $structureManager,
        array $formatFilePaths
    ) {
        $this->templating = $templating;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->structureManager = $structureManager;
        $this->formatFilePaths = $formatFilePaths;
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param string $uuid
     *
     * @return string
     *
     * @throws \Exception
     */
    public function export(
        $webspaceKey,
        $locale,
        $format = '1.2.xliff',
        $uuid = null
    ) {
        if (!$webspaceKey || !$locale) {
            throw new \Exception(sprintf('Invalid parameters for export "%s (%s)"', $webspaceKey, $locale));
        }

        return $this->templating->render(
            $this->getTemplate($format),
            $this->getParameters($webspaceKey, $locale, $format, $uuid)
        );
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param string $uuid
     *
     * @return array
     */
    protected function getParameters($webspaceKey, $locale, $format = '1.2.xliff', $uuid = null)
    {
        /** @var \Sulu\Bundle\ContentBundle\Document\PageDocument[] $documents */
        $documents = $this->getDocuments($webspaceKey, $locale, $uuid);
        $metaDataList = [];
        /** @var \Sulu\Bundle\ContentBundle\Document\PageDocument[] $loadedDocuments */
        $loadedDocuments = array();
        $extensionDataList = array();

        foreach ($documents as $key => $document) {
            $loadedDocument = $this->documentManager->find($document->getUuid(), $locale);
            $loadedDocuments[$key] = $loadedDocument;
            /** @var \Sulu\Component\Content\Metadata\StructureMetadata $metaData */
            $metaData = $this->documentInspector->getStructureMetadata($document);
            $metaDataList[] = $metaData;

            $extensionExportData = array();

            foreach ($document->getExtensionsData()->toArray() as $extensionName => $extensionData) {
                /** @var \Sulu\Bundle\ContentBundle\Content\Structure\ExcerptStructureExtension $extension */
                $extension = $this->structureManager->getExtension($document->getStructureType(), $extensionName);

                if ($extension instanceof ExportExtensionInterface) {
                    $extensionExportData[$extensionName] = $extension->export($extensionData, $format);
                }
            }

            $extensionDataList[] = $extensionExportData;
        }

        return array(
            'webspaceKey' => $webspaceKey,
            'locale' => $locale,
            'format' => $format,
            'documents' => $loadedDocuments,
            'extensionDataList' => $extensionDataList,
            'metaDataList' => $metaDataList,
        );
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $uuid
     *
     * @return array
     */
    protected function getDocuments($webspaceKey, $locale, $uuid = null)
    {
        $queryString = $this->getDocumentsQueryString($webspaceKey, $locale, $uuid);

        $query = $this->documentManager->createQuery($queryString);

        return $query->execute();
    }

    /**
     * @param $webspaceKey
     * @param $locale
     * @param string $uuid
     *
     * @return string
     */
    protected function getDocumentsQueryString($webspaceKey, $locale, $uuid = null)
    {
        $where = [];

        // only pages
        $where[] = '([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';

        // filter by webspace key
        $where[] = sprintf(
            'ISDESCENDANTNODE("/cmf/%s")',
            $webspaceKey
        );

        // filter by locale
        $where[] = sprintf(
            '[i18n:%s-template] IS NOT NULL',
            $locale
        );

        // filter by uuid
        if ($uuid) {
            $where[] = sprintf('[jcr:uuid] = "%s"', $uuid);
        }

        $queryString = 'SELECT * FROM [nt:unstructured] AS a WHERE ' . implode(' AND ', $where);

        return $queryString;
    }

    /**
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
}
