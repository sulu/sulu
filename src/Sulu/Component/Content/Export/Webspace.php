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

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Extension\ExportExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
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
     * @var DocumentManager
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
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

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
     * @param ExtensionManagerInterface $extensionManager
     * @param ContentExportManagerInterface $contentExportManager
     * @param array $formatFilePaths
     */
    public function __construct(
        EngineInterface $templating,
        DocumentManager $documentManager,
        DocumentInspector $documentInspector,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ContentExportManagerInterface $contentExportManager,
        array $formatFilePaths
    ) {
        $this->templating = $templating;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
        $this->structureManager = $structureManager;
        $this->extensionManager = $extensionManager;
        $this->contentExportManager = $contentExportManager;
        $this->formatFilePaths = $formatFilePaths;
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return string
     *
     * @throws \Exception
     */
    public function export(
        $webspaceKey,
        $locale,
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        if (!$webspaceKey || !$locale) {
            throw new \Exception(sprintf('Invalid parameters for export "%s (%s)"', $webspaceKey, $locale));
        }

        return $this->templating->render(
            $this->getTemplate($format),
            $this->getExportData($webspaceKey, $locale, $format, $uuid, $nodes, $ignoredNodes)
        );
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $format
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return array
     */
    public function getExportData(
        $webspaceKey,
        $locale,
        $format = '1.2.xliff',
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        /** @var \Sulu\Bundle\ContentBundle\Document\PageDocument[] $documents */
        $documents = $this->getDocuments($webspaceKey, $locale, $uuid, $nodes, $ignoredNodes);
        /** @var \Sulu\Bundle\ContentBundle\Document\PageDocument[] $loadedDocuments */
        $documentData = [];

        foreach ($documents as $key => $document) {
            $contentData = $this->getContentData($document, $locale, $format);
            $extensionData = $this->getExtensionData($document, $format);
            $settingData = $this->getSettingData($document, $format);

            $documentData[] = [
                'uuid' => $document->getUuid(),
                'locale' => $document->getLocale(),
                'content' => $contentData,
                'settings' => $settingData,
                'extensions' => $extensionData,
            ];
        }

        return [
            'webspaceKey' => $webspaceKey,
            'locale' => $locale,
            'format' => $format,
            'documents' => $documentData,
        ];
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
        $contentData = [];

        foreach ($properties as $property) {
            if (
                $this->contentExportManager->hasExport($property->getType(), $format)
            ) {
                $propertyValue = $propertyValues[$property->getName()];
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
        return $this->createProperty(
            $property->getName(),
            $this->contentExportManager->export($property->getType(), $propertyValue),
            $this->contentExportManager->getOptions($property->getType(), $format),
            $property->getType()
        );
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
        $children = [];

        $blockDataList = $this->contentExportManager->export($property->getType(), $propertyValue);

        foreach ($blockDataList as $blockData) {
            $blockType = $blockData['type'];

            $block = $this->getPropertiesContentData(
                $property->getComponentByName($blockType)->getChildren(),
                $blockData,
                $format
            );

            $block['type'] = $this->createProperty(
                'type',
                $blockType,
                $this->contentExportManager->getOptions($property->getType(), $format),
                $property->getType() . '_type'
            );

            $children[] = $block;
        }

        return $this->createProperty(
            $property->getName(),
            null,
            $this->contentExportManager->getOptions($property->getType(), $format),
            $property->getType(),
            $children
        );
    }

    /**
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

    /**
     * @param BasePageDocument $document
     * @param string $format
     *
     * @return array
     */
    protected function getExtensionData(BasePageDocument $document, $format)
    {
        $extensionData = [];

        foreach ($document->getExtensionsData()->toArray() as $extensionName => $extensionProperties) {
            /** @var \Sulu\Bundle\ContentBundle\Content\Structure\ExcerptStructureExtension $extension */
            $extension = $this->extensionManager->getExtension($document->getStructureType(), $extensionName);

            if ($extension instanceof ExportExtensionInterface) {
                $extensionData[$extensionName] = $extension->export($extensionProperties, $format);
            }
        }

        return $extensionData;
    }

    /**
     * @param BasePageDocument $document
     * @param string $format
     *
     * @return array
     */
    protected function getSettingData(BasePageDocument $document, $format)
    {
        if ($parent = $document->getParent()) {
            $parent = $document->getParent()->getUuid();
        }

        if ($created = $document->getCreated()) {
            $created = $created->format('c');
        }

        if ($changed = $document->getChanged()) {
            $changed = $changed->format('c');
        }

        if ($published = $document->getCreated()) {
            $published = $published->format('c');
        }

        $settingOptions = [];
        if ($format === '1.2.xliff') {
            $settingOptions = ['translate' => false];
        }

        return [
            'structureType' => $this->createProperty('structureType', $document->getStructureType(), $settingOptions),
            'published' => $this->createProperty('published', $published, $settingOptions),
            'created' => $this->createProperty('created', $created, $settingOptions),
            'changed' => $this->createProperty('changed', $changed, $settingOptions),
            'creator' => $this->createProperty('creator', $document->getCreator(), $settingOptions),
            'changer' => $this->createProperty('changer', $document->getChanger(), $settingOptions),
            'navigationContexts' => $this->createProperty(
                'navigationContexts',
                json_encode($document->getNavigationContexts()),
                $settingOptions
            ),
            'permissions' => $this->createProperty(
                'permissions',
                json_encode($document->getPermissions()),
                $settingOptions
            ),
            'shadowLocale' => $this->createProperty('shadowLocale', $document->getShadowLocale(), $settingOptions),
            'webspaceName' => $this->createProperty('webspaceName', $document->getWebspaceName(), $settingOptions),
            'redirectExternal' => $this->createProperty(
                'redirectExternal',
                $document->getRedirectExternal(),
                $settingOptions),
            'redirectType' => $this->createProperty('redirectType', $document->getRedirectType(), $settingOptions),
            'workflowStage' => $this->createProperty('workflowStage', $document->getWorkflowStage(), $settingOptions),
            'parent' => $this->createProperty('parent', $parent, $settingOptions),
        ];
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return array
     */
    protected function getDocuments(
        $webspaceKey,
        $locale,
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        $queryString = $this->getDocumentsQueryString($webspaceKey, $locale, $uuid, $nodes, $ignoredNodes);

        $query = $this->documentManager->createQuery($queryString);

        return $query->execute();
    }

    /**
     * @param $webspaceKey
     * @param $locale
     * @param string $uuid
     * @param array $nodes
     * @param array $ignoredNodes
     *
     * @return string
     */
    protected function getDocumentsQueryString(
        $webspaceKey,
        $locale,
        $uuid = null,
        $nodes = null,
        $ignoredNodes = null
    ) {
        $where = [];

        // only pages
        $where[] = '([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home")';

        // filter by webspace key
        $where[] = sprintf(
            '(ISDESCENDANTNODE("/cmf/%s/contents") OR ISSAMENODE("/cmf/%s/contents"))',
            $webspaceKey,
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

        $nodeWhere = $this->buildNodeUuidToPathWhere($nodes, false);
        if ($nodeWhere) {
            $where[] = $nodeWhere;
        }

        $ignoreWhere = $this->buildNodeUuidToPathWhere($ignoredNodes, true);
        if ($ignoreWhere) {
            $where[] = $ignoreWhere;
        }

        $queryString = 'SELECT * FROM [nt:unstructured] AS a WHERE ' . implode(' AND ', $where);

        return $queryString;
    }

    /**
     * @param $nodes
     * @param bool|false $not
     *
     * @return string
     */
    protected function buildNodeUuidToPathWhere($nodes, $not = false)
    {
        if ($nodes) {
            $paths = $this->getPathsByUuids($nodes);

            $wheres = [];
            foreach ($nodes as $key => $uuid) {
                if (isset($paths[$uuid])) {
                    $wheres[] = sprintf('ISDESCENDANTNODE("%s")', $paths[$uuid]);
                }
            }

            if (!empty($wheres)) {
                return ($not ? 'NOT ' : '') .  '(' . implode(' OR ', $wheres) . ')';
            }
        }
    }

    /**
     * @param $uuids
     *
     * @return string[]
     */
    protected function getPathsByUuids($uuids)
    {
        $paths = [];

        $where = [];
        foreach ($uuids as $uuid) {
            $where[] = sprintf('[jcr:uuid] = "%s"', $uuid);
        }

        $queryString = 'SELECT * FROM [nt:unstructured] AS a WHERE ' . implode(' OR ', $where);

        $query = $this->documentManager->createQuery($queryString);

        $result = $query->execute();

        /** @var BasePageDocument $page */
        foreach ($result as $page) {
            $paths[$page->getUuid()] = $page->getPath();
        }

        return $paths;
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
