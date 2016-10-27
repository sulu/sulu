<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\Content\Document\Subscriber\WorkflowStageSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentInspector as BaseDocumentInspector;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * This class infers information about documents, for example
 * the documents locale, webspace, path, etc.
 *
 * TODO: Add feature to the document manager to map inspectors for each document class
 */
class DocumentInspector extends BaseDocumentInspector
{
    private $metadataFactory;
    private $structureFactory;
    private $namespaceRegistry;
    private $encoder;
    private $webspaceManager;

    public function __construct(
        DocumentRegistry $documentRegistry,
        PathSegmentRegistry $pathSegmentRegistry,
        NamespaceRegistry $namespaceRegistry,
        ProxyFactory $proxyFactory,
        MetadataFactoryInterface $metadataFactory,
        StructureMetadataFactoryInterface $structureFactory,
        PropertyEncoder $encoder,
        WebspaceManagerInterface $webspaceManager
    ) {
        parent::__construct($documentRegistry, $pathSegmentRegistry, $proxyFactory);
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
        $this->namespaceRegistry = $namespaceRegistry;
        $this->encoder = $encoder;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * Return the webspace name for the given document.
     *
     * @param object $document
     *
     * @return string
     */
    public function getWebspace($document)
    {
        return $this->extractWebspaceFromPath($this->getPath($document));
    }

    /**
     * Return the path of the document in relation to the content root.
     *
     * TODO: We need a better solution for retrieving webspace paths (the existing
     *       "session manager" is not a good solution).
     *
     * @param PathBehavior $document
     *
     * @return string
     */
    public function getContentPath(PathBehavior $document)
    {
        $path = $this->getPath($document);
        $webspaceKey = $this->getWebspace($document);

        return str_replace(
            sprintf(
                '/%s/%s/%s',
                $this->pathSegmentRegistry->getPathSegment('base'),
                $webspaceKey,
                $this->pathSegmentRegistry->getPathSegment('content')
            ),
            '',
            $path
        );
    }

    /**
     * Return the structure for the given StructureBehavior implementing document.
     *
     * @param StructureBehavior $document
     *
     * @return StructureMetadata
     */
    public function getStructureMetadata(StructureBehavior $document)
    {
        return $this->structureFactory->getStructureMetadata(
            $this->getMetadata($document)->getAlias(),
            $document->getStructureType()
        );
    }

    /**
     * Return the (DocumentManager) Metadata for the given document.
     *
     * @param object $document
     *
     * @return Metadata
     */
    public function getMetadata($document)
    {
        return $this->metadataFactory->getMetadataForClass(get_class($document));
    }

    /**
     * Return the localization state of the node.
     *
     * @param object $document
     *
     * @return string
     */
    public function getLocalizationState($document)
    {
        if ($document instanceof ShadowLocaleBehavior) {
            if (true === $document->isShadowLocaleEnabled()) {
                return LocalizationState::SHADOW;
            }
        }

        $originalLocale = $document->getOriginalLocale();
        $currentLocale = $document->getLocale();

        if ($originalLocale === $currentLocale) {
            return LocalizationState::LOCALIZED;
        }

        return LocalizationState::GHOST;
    }

    /**
     * Return the locale for the given document or null
     * if the document is not managed.
     *
     * @return string|null
     */
    public function getLocale($document)
    {
        if ($document instanceof LocaleBehavior) {
            return $document->getLocale();
        }

        if ($this->documentRegistry->hasDocument($document)) {
            return $this->documentRegistry->getLocaleForDocument($document);
        }

        return;
    }

    /**
     * Return the original (requested) locale for this document before
     * any fallback logic was applied to it.
     *
     * @param object $document
     *
     * @return string
     */
    public function getOriginalLocale($document)
    {
        return $this->documentRegistry->getOriginalLocaleForDocument($document);
    }

    /**
     * Return the concrete localizations for the given document.
     *
     * @param object $document
     *
     * @return array
     */
    public function getLocales($document)
    {
        $locales = [];
        $node = $this->getNode($document);
        $prefix = $this->namespaceRegistry->getPrefix('system_localized');

        foreach ($node->getProperties() as $property) {
            preg_match(
                sprintf('/^%s:([a-zA-Z_]*?)-.*/', $prefix),
                $property->getName(),
                $matches
            );

            if ($matches) {
                $locales[$matches[1]] = $matches[1];
            }
        }

        return array_values(array_unique($locales));
    }

    /**
     * Return locales which are not shadows.
     *
     * @param ShadowLocaleBehavior $document
     *
     * @return array
     */
    public function getConcreteLocales($document)
    {
        $locales = $this->getLocales($document);

        if ($document instanceof ShadowLocaleBehavior) {
            $locales = array_diff($locales, $this->getShadowLocales($document));
        }

        return array_values($locales);
    }

    /**
     * Return the enabled shadow locales for the given document.
     *
     * @param ShadowLocaleBehavior $document
     *
     * @return array
     */
    public function getShadowLocales(ShadowLocaleBehavior $document)
    {
        $shadowLocales = [];
        $locales = $this->getLocales($document);
        $node = $this->getNode($document);
        foreach ($locales as $locale) {
            $shadowEnabledName = $this->encoder->localizedSystemName(
                ShadowLocaleSubscriber::SHADOW_ENABLED_FIELD,
                $locale
            );
            $shadowLocaleName = $this->encoder->localizedSystemName(
                ShadowLocaleSubscriber::SHADOW_LOCALE_FIELD,
                $locale
            );

            if ($node->getPropertyValueWithDefault($shadowEnabledName, false)) {
                $shadowLocales[$node->getPropertyValue($shadowLocaleName)] = $locale;
            }
        }

        return $shadowLocales;
    }

    /**
     * Return the published locales for the given document.
     *
     * @param ShadowLocaleBehavior $document
     *
     * @return array
     */
    public function getPublishedLocales(ShadowLocaleBehavior $document)
    {
        $node = $this->getNode($document);
        $locales = $this->getLocales($document);
        $publishedLocales = [];

        foreach ($locales as $locale) {
            $publishedPropertyName = $this->encoder->localizedSystemName(
                WorkflowStageSubscriber::PUBLISHED_FIELD,
                $locale
            );

            if ($node->getPropertyValueWithDefault($publishedPropertyName, false)) {
                $publishedLocales[] = $locale;
            }
        }

        return $publishedLocales;
    }

    /**
     * Returns urls for given page for all locales in webspace.
     *
     * TODO: Implement a router service instead of this.
     *
     * @param BasePageDocument $page
     *
     * @return array
     */
    public function getLocalizedUrlsForPage(BasePageDocument $page)
    {
        $localizedUrls = [];
        $webspaceKey = $this->getWebspace($page);
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $node = $this->getNode($page);

        $structure = $this->getStructureMetadata($page);
        $resourceLocatorProperty = $structure->getPropertyByTagName('sulu.rlp');

        foreach ($webspace->getAllLocalizations() as $localization) {
            $resolvedLocale = $localization->getLocalization();
            $locale = $resolvedLocale;

            $shadowEnabledName = $this->encoder->localizedSystemName(
                ShadowLocaleSubscriber::SHADOW_ENABLED_FIELD,
                $resolvedLocale
            );

            if (true === $node->getPropertyValueWithDefault($shadowEnabledName, false)) {
                $shadowLocaleName = $this->encoder->localizedSystemName(
                    ShadowLocaleSubscriber::SHADOW_LOCALE_FIELD,
                    $resolvedLocale
                );
                $resolvedLocale = $node->getPropertyValue($shadowLocaleName);
            }

            $stageName = $this->encoder->localizedSystemName(
                WorkflowStageSubscriber::WORKFLOW_STAGE_FIELD,
                $resolvedLocale
            );

            if (false === $node->hasProperty($stageName)) {
                continue;
            }

            $stage = $node->getProperty($stageName);

            if (WorkflowStage::PUBLISHED !== $stage->getValue()) {
                continue;
            }

            $url = $node->getPropertyValueWithDefault(
                $this->encoder->localizedContentName($resourceLocatorProperty->getName(), $locale),
                null
            );

            if (null === $url) {
                continue;
            }

            $localizedUrls[$locale] = $url;
        }

        return $localizedUrls;
    }

    /**
     * Extracts webspace key from given path.
     *
     * @param string $path path of node
     *
     * @return string
     */
    private function extractWebspaceFromPath($path)
    {
        $match = preg_match(
            sprintf(
                '/^\/%s\/([\w\.-]*?)\/.*$/',
                $this->pathSegmentRegistry->getPathSegment('base')
            ),
            $path,
            $matches
        );

        if ($match) {
            return $matches[1];
        }

        return;
    }
}
