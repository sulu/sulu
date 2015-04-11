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

/**
 * This class infers information about documents, for example
 * the documents locale, webspace, path, etc.
 */
class DocumentInspector extends BaseDocumentInspector
{
    private $metadataFactory;
    private $structureFactory;

    public function __construct(
        DocumentRegistry $documentRegistry,
        PathSegmentRegistry $pathSegmentRegistry,
        ProxyFactory $proxyFactory,
        MetadataFactory $metadataFactory,
        StructureFactoryInterface $structureFactory
    )
    {
        parent::__construct($documentRegistry, $pathSegmentRegistry, $proxyFactory);
        $this->metadataFactory = $metadataFactory;
        $this->structureFactory = $structureFactory;
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
        } else {
            return null;
        }
    }
}
