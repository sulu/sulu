<?php

namespace Sulu\Bundle\DocumentManagerBundle\Bridge;

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\DocumentInspector as BaseDocumentInspector;
use Sulu\Component\Content\Document\Behavior\ContentBehavior;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Content\Structure\Factory\StructureFactoryInterface;
use Sulu\Component\DocumentManager\MetadataFactory;
use Sulu\Component\DocumentManager\ProxyFactory;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\Content\Document\Subscriber\ContentSubscriber;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Subscriber\ShadowLocaleSubscriber;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Component\Content\Document\Subscriber\WorkflowStageSubscriber;
use Sulu\Component\Content\Document\WorkflowStage;
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
        MetadataFactory $metadataFactory,
        StructureFactoryInterface $structureFactory,
        PropertyEncoder $encoder,
        WebspaceManagerInterface $webspaceManager
    )
    {
        parent::__construct($documentRegistry, $pathSegmentRegistry, $proxyFactory);
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
        $this->namespaceRegistry = $namespaceRegistry;
        $this->encoder = $encoder;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * Return the webspace name for the given document
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
     * Return the path of the document in relation to the content root
     *
     * TODO: We need a better solution for retrieving webspace paths (the existing
     *       "session manager" is not a good solution).
     *
     * @return string
     */
    public function getContentPath(ContentBehavior $document)
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
     * Return the structure for the given ContentBehavior implementing document
     *
     * @param ContentBehavior $document
     *
     * @return Structure
     */
    public function getStructure(ContentBehavior $document)
    {
        return $this->structureFactory->getStructure(
            $this->getMetadata($document)->getAlias(),
            $document->getStructureType()
        );
    }

    /**
     * Return the (DocumentManager) Metadata for the given document
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
     * Return the localization state of the node
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

        $originalLocale = $this->documentRegistry->getOriginalLocaleForDocument($document);
        $currentLocale = $this->documentRegistry->getLocaleForDocument($document);

        if ($originalLocale === $currentLocale) {
            return LocalizationState::LOCALIZED;
        }

        return LocalizationState::GHOST;
    }

    /**
     * Return the locale for the given document
     *
     * @return string
     */
    public function getLocale($document)
    {
        return $this->documentRegistry->getLocaleForDocument($document);
    }

    /**
     * Return the concrete localizations for the given document
     *
     * @param ContentBehavior $document
     *
     * @return array
     */
    public function getLocales(ContentBehavior $document)
    {
        $locales = array();
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
     * Return locales which are not shadows
     *
     * @param object $document
     *
     * @return array
     */
    public function getConcreteLocales(ShadowLocaleBehavior $document)
    {
        return array_diff($this->getLocales($document), $this->getShadowLocales($document));
    }

    /**
     * Return the enabled shadow locales for the given document
     * 
     * @param ShadowLocaleBehavior $document
     *
     * @return array
     */
    public function getShadowLocales(ShadowLocaleBehavior $document)
    {
        $shadowLocales = array();
        $locales = $this->getLocales($document);
        $node = $this->getNode($document);
        foreach ($locales as $locale) {
            $shadowEnabledName = $this->encoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_ENABLED_FIELD, $locale);
            $shadowLocaleName = $this->encoder->localizedSystemName(ShadowLocaleSubscriber::SHADOW_LOCALE_FIELD, $locale);

            if ($node->getPropertyValueWithDefault($shadowEnabledName, false)) {
                $shadowLocales[$node->getPropertyValue($shadowLocaleName)] = $locale;
            }
        }

        return $shadowLocales;
    }

    /**
     * Returns urls for given page for all locales in webspace
     *
     * TODO: Implement a router service instead of this.
     *
     * @param  Page          $page
     * @param  NodeInterface $node
     * @param  string        $webspaceKey
     * @param  string        $segmentKey
     * @return array
     */
    public function getLocalizedUrlsForPage(BasePageDocument $page)
    {
        $localizedUrls = array();
        $webspaceKey = $this->getWebspace($page);
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $node = $this->getNode($page);

        $structure = $this->getStructure($page);
        $rlpProperty = $structure->getPropertyByTagName('sulu.rlp');

        foreach ($webspace->getAllLocalizations() as $localization) {
            $locale = $localization->getLocalization();
            $stageName = $this->encoder->localizedSystemName(WorkflowStageSubscriber::WORKFLOW_STAGE_FIELD, $locale);

            if (false === $node->hasProperty($stageName)) {
                continue;
            }

            $stage = $node->getProperty($stageName);

            if (WorkflowStage::PUBLISHED !== $stage->getValue()) {
                continue;
            }

            $url = $node->getPropertyValueWithDefault(
                $this->encoder->localizedContentName($rlpProperty->getName(), $locale),
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
     * Extracts webspace key from given path
     *
     * @param string $path path of node
     * @return string
     */
    private function extractWebspaceFromPath($path)
    {
        $match = preg_match(sprintf(
            '/^\/%s\/(\w*)\/.*$/',
            $this->pathSegmentRegistry->getPathSegment('base')
        ), $path, $matches);

        if ($match) {
            return $matches[1];
        }

        return null;
    }
}
