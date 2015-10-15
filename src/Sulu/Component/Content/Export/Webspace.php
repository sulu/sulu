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

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata;
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
     * @var ContentExportManagerInterface
     */
    protected $contentExportManager;

    /**
     * @var string[]
     */
    protected $formatFilePaths;

    /**
     * @param EngineInterface $templating
     * @param DocumentManager $documentManager
     * @param DocumentInspector $documentInspector
     * @param StructureManagerInterface $structureManager
     * @param ContentExportManagerInterface $contentExportManager
     * @param array $formatFilePaths
     */
    public function __construct(
        EngineInterface $templating,
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        StructureManagerInterface $structureManager,
        ContentExportManagerInterface $contentExportManager,
        array $formatFilePaths
    ) {
        $this->templating = $templating;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->structureManager = $structureManager;
        $this->contentExportManager = $contentExportManager;
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
            $this->getExportData($webspaceKey, $locale, $format, $uuid)
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
    public function getExportData(
        $webspaceKey,
        $locale,
        $format = '1.2.xliff',
        $uuid = null
    ) {
        /** @var \Sulu\Bundle\ContentBundle\Document\PageDocument[] $documents */
        $documents = $this->getDocuments($webspaceKey, $locale, $uuid);
        /** @var \Sulu\Bundle\ContentBundle\Document\PageDocument[] $loadedDocuments */
        $documentData = array();

        foreach ($documents as $key => $document) {
            $contentData = $this->getContentData($document, $locale, $format);
            $extensionData = $this->getExtensionData($document, $format);

            $documentData[] = array(
                'uuid' => $document->getUuid(),
                'locale' => $document->getLocale(),
                'structureType' => $document->getStructureType(),
                'content' => $contentData,
                'extensions' => $extensionData,
            );
        }

        return array(
            'webspaceKey' => $webspaceKey,
            'locale' => $locale,
            'format' => $format,
            'documents' => $documentData,
        );
    }

    /**
     * @param BasePageDocument $document
     * @param $locale
     * @param $format
     *
     * @return array
     */
    protected function getContentData(BasePageDocument $document, $locale, $format)
    {
        /** @var BasePageDocument $loadedDocument */
        $loadedDocument = $this->documentManager->find($document->getUuid(), $locale);

        /** @var \Sulu\Component\Content\Metadata\StructureMetadata $metaData */
        $metaData = $this->documentInspector->getStructureMetadata($document);

        $propertyValues = $loadedDocument->getStructure()->toArray();
        $properties = $metaData->getProperties();

        $contentData = $this->getPropertiesContentData($properties, $propertyValues, $format);

        return $contentData;
    }

    /**
     * @param PropertyMetadata[] $properties
     * @param $propertyValues
     * @param $format
     *
     * @return array
     */
    protected function getPropertiesContentData($properties, $propertyValues, $format)
    {
        $contentData = array();

        foreach ($properties as $property) {
            if (
                $this->contentExportManager->hasExport($property->getType(), $format)
                && $propertyValue = $propertyValues[$property->getName()]
            ) {
                if ($property instanceof BlockMetadata) {
                    $data = $this->getBlockPropertyData($property, $propertyValue, $format);
                } else {
                    $data = $this->getPropertyData($property, $propertyValue, $format);
                }

                $contentData[$property->getName()] = $data;
            }
        }

        return $contentData;
    }

    /**
     * @param PropertyMetadata $property
     * @param PropertyValue $propertyValue
     * @param string $format
     *
     * @return array
     */
    protected function getPropertyData(PropertyMetadata $property, $propertyValue, $format)
    {
        return [
            'name' => $property->getName(),
            'value' => $this->contentExportManager->export($property->getType(), $propertyValue),
            'type' => $property->getType(),
            'options' => $this->contentExportManager->getOptions($property->getType(), $format),
        ];
    }

    /**
     * @param BlockMetadata $property
     * @param PropertyValue $propertyValue
     * @param $format
     *
     * @return array
     */
    protected function getBlockPropertyData(BlockMetadata $property, $propertyValue, $format)
    {
        $children = array();

        $blockDataList = $this->contentExportManager->export($property->getType(), $propertyValue);

        foreach ($blockDataList as $blockData) {
            $blockType = $blockData['type'];

            $block = $this->getPropertiesContentData(
                $property->getComponentByName($blockType)->getChildren(),
                $blockData,
                $format
            );

            $block['type'] = [
                'name' => 'type',
                'value' => $blockType,
                'type' => $property->getType() . '_type',
                'options' => $this->contentExportManager->getOptions($property->getType(), $format),
            ];


            $children[] = $block;
        }

        return array(
            'name' => $property->getName(),
            'type' => $property->getType(),
            'children' => $children,
            'options' => $this->contentExportManager->getOptions($property->getType(), $format),
        );
    }

    /**
     * @param BasePageDocument $document
     * @param string $format
     *
     * @return array
     */
    protected function getExtensionData(BasePageDocument $document, $format)
    {
        $extensionData = array();

        foreach ($document->getExtensionsData()->toArray() as $extensionName => $extensionProperties) {
            /** @var \Sulu\Bundle\ContentBundle\Content\Structure\ExcerptStructureExtension $extension */
            $extension = $this->structureManager->getExtension($document->getStructureType(), $extensionName);

            if ($extension instanceof ExportExtensionInterface) {
                $extensionData[$extensionName] = $extension->export($extensionProperties, $format);
            }
        }

        return $extensionData;
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
