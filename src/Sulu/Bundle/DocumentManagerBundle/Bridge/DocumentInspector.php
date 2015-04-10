<?php

namespace Sulu\Bundle\DocumentManagerBundle\Bridge;

use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\PathSegmentRegistry;
use Sulu\Component\DocumentManager\DocumentInspector as BaseDocumentInspector;

/**
 * This class infers information about documents, for example
 * the documents locale, webspace, path, etc.
 */
class DocumentInspector extends BaseDocumentInspector
{
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
