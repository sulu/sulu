<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * Handles the mapping between managed documents and nodes.
 */
class DocumentRegistry
{
    /**
     * @var array
     */
    private $documentMap = [];

    /**
     * @var array
     */
    private $documentNodeMap = [];

    /**
     * @var array
     */
    private $nodeMap = [];

    /**
     * @var array
     */
    private $nodeDocumentMap = [];

    /**
     * @var array
     */
    private $documentLocaleMap = [];

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var array
     */
    private $hydrationState = [];

    /**
     * @param string $defaultLocale
     */
    public function __construct($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Register a document.
     *
     * @param string $locale
     *
     * @throws DocumentManagerException
     */
    public function registerDocument($document, NodeInterface $node, $locale)
    {
        $oid = $this->getObjectIdentifier($document);
        $uuid = $node->getIdentifier();

        // do not allow nodes without UUIDs or reregistration of documents
        $this->validateDocumentRegistration($document, $locale, $node, $oid, $uuid);

        $this->documentMap[$oid] = $document;
        $this->documentNodeMap[$oid] = $uuid;
        $this->nodeMap[$node->getIdentifier()] = $node;
        $this->nodeDocumentMap[$this->getNodeLocaleKey($node->getIdentifier(), $locale)] = $document;
        $this->documentLocaleMap[$oid] = $locale;
    }

    /**
     * Return true if the document is managed.
     *
     * @param object $document
     *
     * @return bool
     */
    public function hasDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);

        return isset($this->documentMap[$oid]);
    }

    /**
     * Return true if the node is managed.
     *
     * @param string $locale
     *
     * @return bool
     */
    public function hasNode(NodeInterface $node, $locale)
    {
        return \array_key_exists($this->getNodeLocaleKey($node->getIdentifier(), $locale), $this->nodeDocumentMap);
    }

    /**
     * Clear the registry (detach all documents).
     */
    public function clear()
    {
        $this->documentMap = [];
        $this->documentNodeMap = [];
        $this->nodeMap = [];
        $this->nodeDocumentMap = [];
        $this->documentLocaleMap = [];
        $this->hydrationState = [];
    }

    /**
     * Remove all references to the given document and its
     * associated node.
     *
     * @param object $document
     */
    public function deregisterDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);
        $this->assertDocumentExists($document);
        $nodeIdentifier = $this->documentNodeMap[$oid];
        $locale = $this->documentLocaleMap[$oid];

        unset($this->nodeMap[$nodeIdentifier]);
        unset($this->nodeDocumentMap[$this->getNodeLocaleKey($nodeIdentifier, $locale)]);
        unset($this->documentMap[$oid]);
        unset($this->documentNodeMap[$oid]);
        unset($this->documentLocaleMap[$oid]);
        unset($this->hydrationState[$oid]);
    }

    /**
     * Return the node for the given managed document.
     *
     * @param object $document
     *
     * @return NodeInterface
     *
     * @throws \RuntimeException If the node is not managed
     */
    public function getNodeForDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);
        $this->assertDocumentExists($document);

        return $this->nodeMap[$this->documentNodeMap[$oid]];
    }

    /**
     * Return the current locale for the given document.
     *
     * @param object $document
     *
     * @return string
     */
    public function getLocaleForDocument($document)
    {
        $oid = $this->getObjectIdentifier($document);
        $this->assertDocumentExists($document);

        return $this->documentLocaleMap[$oid];
    }

    /**
     * Return the original locale for the document.
     *
     * @param object $document
     *
     * @return string
     */
    public function getOriginalLocaleForDocument($document)
    {
        $this->assertDocumentExists($document);

        if ($document instanceof LocaleBehavior) {
            return $document->getOriginalLocale() ?: $document->getLocale();
        }

        return $this->getLocaleForDocument($document);
    }

    /**
     * Return the document for the given managed node.
     *
     * @param string $locale
     *
     * @return mixed If the node is not managed
     */
    public function getDocumentForNode(NodeInterface $node, $locale)
    {
        $identifier = $node->getIdentifier();
        $this->assertNodeExists($identifier);

        return $this->nodeDocumentMap[$this->getNodeLocaleKey($node->getIdentifier(), $locale)];
    }

    /**
     * Return the default locale.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @param object $document
     */
    private function assertDocumentExists($document)
    {
        $oid = \spl_object_hash($document);

        if (!isset($this->documentMap[$oid])) {
            throw new \RuntimeException(\sprintf(
                'Document "%s" with OID "%s" is not managed, there are "%s" managed objects,',
                \get_class($document), $oid, \count($this->documentMap)
            ));
        }
    }

    private function assertNodeExists($identifier)
    {
        if (!isset($this->nodeMap[$identifier])) {
            throw new \RuntimeException(\sprintf(
                'Node with identifier "%s" is not managed, there are "%s" managed objects,',
                $identifier, \count($this->documentMap)
            ));
        }
    }

    /**
     * Get the spl object hash for the given object.
     *
     * @param object $document
     *
     * @return string
     */
    private function getObjectIdentifier($document)
    {
        return \spl_object_hash($document);
    }

    /**
     * Ensure that the document is not already registered and that the node
     * has a UUID.
     *
     * @param object $document
     * @param string $oid
     * @param string $uuid
     *
     * @throws DocumentManagerException
     */
    private function validateDocumentRegistration($document, $locale, NodeInterface $node, $oid, $uuid)
    {
        if (null === $uuid) {
            throw new DocumentManagerException(\sprintf(
                'Node "%s" of type "%s" has no UUID. Only referencable nodes can be registered by the document manager',
                $node->getPath(), $node->getPrimaryNodeType()->getName()
            ));
        }

        $documentNodeKey = $this->getNodeLocaleKey($node->getIdentifier(), $locale);
        if (\array_key_exists($uuid, $this->nodeMap) && \array_key_exists($documentNodeKey, $this->nodeDocumentMap)) {
            $registeredDocument = $this->nodeDocumentMap[$documentNodeKey];

            throw new \RuntimeException(\sprintf(
                'Document "%s" (%s) is already registered for node "%s" (%s) when trying to register document "%s" (%s)',
                \spl_object_hash($registeredDocument),
                \get_class($registeredDocument),
                $uuid,
                $node->getPath(),
                $oid,
                \get_class($document)
            ));
        }
    }

    /**
     * Register that the document has been hydrated and that it should
     * not be hydrated again.
     *
     * @param object $document
     */
    public function markDocumentAsHydrated($document)
    {
        $oid = \spl_object_hash($document);
        $this->hydrationState[$oid] = true;
    }

    /**
     * Unmark the document as being hydrated. It will then be
     * rehydrated the next time a HYDRATE event is fired for ot.
     *
     * @param object $document
     */
    public function unmarkDocumentAsHydrated($document)
    {
        $oid = \spl_object_hash($document);

        unset($this->hydrationState[$oid]);
    }

    /**
     * Return true if the document is a candidate for hydration/re-hydration.
     *
     * @param object $document
     *
     * @return bool
     */
    public function isHydrated($document)
    {
        $oid = \spl_object_hash($document);

        if (isset($this->hydrationState[$oid])) {
            return true;
        }

        return false;
    }

    /**
     * Returns array key for given uuid and locale.
     *
     * @param string $uuid
     * @param string $locale
     *
     * @return string
     */
    private function getNodeLocaleKey($uuid, $locale)
    {
        return \sprintf('%s_%s', $uuid, $locale);
    }
}
