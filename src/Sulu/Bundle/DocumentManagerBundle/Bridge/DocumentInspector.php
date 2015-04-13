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

/**
 * This class infers information about documents, for example
 * the documents locale, webspace, path, etc.
 */
class DocumentInspector extends BaseDocumentInspector
{
    private $metadataFactory;
    private $structureFactory;
    private $namespaceRegistry;

    public function __construct(
        DocumentRegistry $documentRegistry,
        PathSegmentRegistry $pathSegmentRegistry,
        NamespaceRegistry $namespaceRegistry,
        ProxyFactory $proxyFactory,
        MetadataFactory $metadataFactory,
        StructureFactoryInterface $structureFactory
    )
    {
        parent::__construct($documentRegistry, $pathSegmentRegistry, $proxyFactory);
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
        $this->namespaceRegistry = $namespaceRegistry;
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
                sprintf('/^%s:([a-zA-Z_]*?)-%s/', $prefix, ContentSubscriber::STRUCTURE_TYPE_FIELD),
                $property->getName(),
                $matches
            );

            if ($matches) {
                $locales[$matches[1]] = $matches[1];
            }
        }

        return array_values($locales);
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
