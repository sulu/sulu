<?php

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;

/**
 * Handles the mapping between managed documents and nodes
 *
 * TODO: There is currently no rollback support -- i.e. if a document
 *       is deregistered but the PHPCR session fails to save, then the document
 *       will remain deregistered here and we will have inconsistent state.
 */
class DocumentRegistry
{
    /**
     * @var array
     */
    private $documentMap;

    /**
     * @var array
     */
    private $documentNodeMap;

    /**
     * @var array
     */
    private $nodeDocumentMap;

    /**
     * @var array
     */
    private $documentLocaleMap;

    /**
     * Register a document
     *
     * @param mixed $document
     * @param NodeInterface $node
     */
    public function registerDocument($document, NodeInterface $node, $locale)
    {
        $oid = spl_object_hash($document);
        $this->documentMap[$oid] = $document;
        $this->documentNodeMap[$oid] = $node;
        $this->nodeDocumentMap[$node->getIdentifier()] = $document;
        $this->documentLocaleMap[$oid] = $locale;
    }

    /**
     * Return true if the document is managed
     *
     * @param object $document
     *
     * @return boolean
     */
    public function hasDocument($document)
    {
        $oid = spl_object_hash($document);

        return isset($this->documentMap[$oid]);
    }

    /**
     * Return true if the node is managed
     *
     * @param NodeInterface $node
     * @return boolean
     */
    public function hasNode(NodeInterface $node)
    {
        return isset($this->nodeDocumentMap[$node->getIdentifier()]);
    }

    /**
     * Clear the registry (detach all documents)
     */
    public function clear()
    {
        $this->documentMap = array();
        $this->documentNodeMap = array();
        $this->nodeDocumentMap = array();
    }

    /**
     * Remove all references to the given document and its
     * associated node.
     *
     * @param object $document
     */
    public function deregisterDocument($document)
    {
        $oid = spl_object_hash($document);
        $this->assertDocumentExists($oid);

        $node = $this->documentNodeMap[$oid];
        $nodeIdentifier = $node->getIdentifier();

        unset($this->nodeDocumentMap[$nodeIdentifier]);
        unset($this->documentMap[$oid]);
        unset($this->documentNodeMap[$oid]);
        unset($this->documentLocaleMap[$oid]);
    }

    /**
     * Return the node for the given managed document
     *
     * @param object $document
     * @throws \RuntimeException If the node is not managed
     * @return NodeInterface
     */
    public function getNodeForDocument($document)
    {
        $oid = spl_object_hash($document);
        $this->assertDocumentExists($oid);

        return $this->documentNodeMap[$oid];
    }

    /**
     * Return the current locale for the given document
     *
     * @param object $document
     *
     * @return string
     */
    public function getLocaleForDocument($document)
    {
        $oid = spl_object_hash($document);
        $this->assertDocumentExists($oid);

        return $this->documentLocaleMap[$oid];
    }

    /**
     * Return the document for the given managed node
     *
     * @param NodeInterface $node
     * @throws \RuntimeException If the node is not managed
     */
    public function getDocumentForNode(NodeInterface $node)
    {
        $identifier = $node->getIdentifier();
        $this->assertNodeExists($identifier);

        return $this->nodeDocumentMap[$identifier];
    }

    /**
     * @param mixed $oid
     */
    private function assertDocumentExists($oid)
    {
        if (!isset($this->documentMap[$oid])) {
            throw new \RuntimeException(sprintf(
                'Document with OID "%s" is not managed, there are "%s" managed objects,',
                $oid, count($this->documentMap)
            ));
        }
    }

    /**
     * @param mixed $identifier
     */
    private function assertNodeExists($identifier)
    {
        if (!isset($this->nodeDocumentMap[$identifier])) {
            throw new \RuntimeException(sprintf(
                'Node with identifier "%s" is not managed, there are "%s" managed objects,',
                $identifier, count($this->documentMap)
            ));
        }
    }
}
