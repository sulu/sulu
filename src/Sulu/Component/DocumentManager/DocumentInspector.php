<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

use PHPCR\NodeInterface;

/**
 * This class infers information about documents, for example
 * the documents locale, webspace, path, etc.
 */
class DocumentInspector
{
    /**
     * @var DocumentRegistry
     */
    protected $documentRegistry;

    /**
     * @var PathSegmentRegistry
     */
    protected $pathSegmentRegistry;

    /**
     * @var ProxyFactory
     */
    protected $proxyFactory;

    /**
     * @param DocumentRegistry $documentRegistry
     * @param PathSegmentRegistry $pathSegmentRegistry
     * @param ProxyFactory $proxyFactory
     */
    public function __construct(
        DocumentRegistry $documentRegistry,
        PathSegmentRegistry $pathSegmentRegistry,
        ProxyFactory $proxyFactory
    ) {
        $this->documentRegistry = $documentRegistry;
        $this->pathSegmentRegistry = $pathSegmentRegistry;
        $this->proxyFactory = $proxyFactory;
    }

    /**
     * Return the parent document for the given document.
     *
     * @param object $document
     *
     * @return object|null
     */
    public function getParent($document)
    {
        $parentNode = $this->getNode($document)->getParent();

        if (!$parentNode) {
            return;
        }

        return $this->proxyFactory->createProxyForNode($document, $parentNode);
    }

    /**
     * Get referrers for the document.
     *
     * @param object $document
     *
     * @return Collection\ReferrerCollection
     */
    public function getReferrers($document)
    {
        return $this->proxyFactory->createReferrerCollection($document);
    }

    /**
     * Return the PHPCR node for the given document.
     *
     * @param object $document
     *
     * @return NodeInterface
     */
    public function getNode($document)
    {
        return $this->documentRegistry->getNodeForDocument($document);
    }

    /**
     * Returns lazy-loading children collection for given document.
     *
     * @param object $document
     * @param array $options
     *
     * @return Collection\ChildrenCollection
     */
    public function getChildren($document, array $options = [])
    {
        return $this->proxyFactory->createChildrenCollection($document, $options);
    }

    /**
     * Return true if the document has children.
     *
     * @param object $document
     *
     * @return bool
     */
    public function hasChildren($document)
    {
        return $this->getNode($document)->hasNodes();
    }

    /**
     * Return the locale for the given document.
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
     * Return the original (requested) locale for the given document.
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
     * Return the depth of the given document within the content repository.
     *
     * @param $document
     *
     * @return int
     */
    public function getDepth($document)
    {
        return $this->getNode($document)->getDepth();
    }

    /**
     * Return the name of the document.
     *
     * @param $document
     *
     * @return string
     */
    public function getName($document)
    {
        return $this->getNode($document)->getName();
    }

    /**
     * Return the path for the given document.
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
     * Return the UUID of the given document.
     *
     * @param object $document
     *
     * @return string
     */
    public function getUuid($document)
    {
        return $this->documentRegistry
            ->getNodeForDocument($document)
            ->getIdentifier();
    }
}
