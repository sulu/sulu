<?php

namespace Sulu\Component\Content\Document;

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;

/**
 * This class infers information about documents, for example
 * the documents locale, webspace, path, etc.
 */
class DocumentInspector
{
    private $documentRegistry;
    private $pathSegmentRegistry;

    public function __construct(
        DocumentRegistry $documentRegistry,
        PathSegmentRegistry $pathSegmentregistry
    )
    {
        $this->documentRegistry = $documentRegistry;
        $this->pathSegmentRegistry = $pathSegmentregistry;
    }

    /**
     * Return the locale for the given document
     *
     * @param object $document
     *
     * @return string
     */
    public function getLocale($document)
    {
        return $this->documentRegistry->getLocaleForDocument($document);
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
     * Return the path for the given document
     *
     * @param object $document
     *
     * @return string
     */
    public function getPath($document)
    {
        return $this->documentRegistry
            ->getNodeForDocument($document)
            ->getPath();
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
