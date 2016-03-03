<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Syncronization;

use PHPCR\Util\PathHelper;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Class responsible for registering a document from the default document manager
 * with the publish document manager including any documents dependent on
 * the document to be synchronized.
 */
class DocumentRegistrator
{
    /**
     * @var DocumentManagerInterface
     */
    private $defaultManager;

    /**
     * @var DocumentManagerInterface
     */
    private $publishManager;

    public function __construct(
        DocumentManagerInterface $defaultManager,
        DocumentManagerInterface $publishManager
    ) {
        $this->defaultManager = $defaultManager;
        $this->publishManager = $publishManager;
    }

    /**
     * Register the incoming DDM document with any existing PHPCR node in the
     * PDM.
     *
     * If the PDM already has the incoming PHPCR node then we need to register
     * the existing PHPCR node from the PDM PHPCR session with the incoming DDM
     * document (otherwise the system will attempt to create a new document and
     * fail).
     *
     * We also ensure that any immediately related documents are also registered.
     *
     * @param SynchronizeBehavior $document
     */
    public function registerDocumentWithPDM(SynchronizeBehavior $document)
    {
        $this->registerSingleDocumentWithPDM($document);

        $metadata = $this->defaultManager->getMetadataFactory()->getMetadataForClass(get_class($document));
        // iterate over the field mappings for the document, if they resolve to
        // an object then try and register it with the PDM.
        foreach (array_keys($metadata->getFieldMappings()) as $field) {
            $propertyValue = $metadata->getFieldValue($document, $field);

            if (false === is_object($propertyValue)) {
                continue;
            }

            // if the default document manager does not have this object then it is
            // not a candidate for being persisted (e.g. it might be a \DateTime
            // object).
            if (false === $this->defaultManager->getRegistry()->hasDocument($propertyValue)) {
                continue;
            }

            $this->registerSingleDocumentWithPDM($propertyValue);
        }

        // TODO: Workaround for the fact that "parent" is not in the metadata,
        // see: https://github.com/sulu-io/sulu-document-manager/issues/67
        if ($document instanceof ParentBehavior) {
            if ($parent = $document->getParent()) {
                $this->registerSingleDocumentWithPDM($parent, true);
            }
        }
    }

    private function registerSingleDocumentWithPDM($document, $create = false)
    {
        $ddmInspector = $this->defaultManager->getInspector();
        $pdmRegistry = $this->publishManager->getRegistry();

        // if the PDM registry already has the document, then
        // there is nothing to do - the document manager will
        // handle the rest.
        if (true === $pdmRegistry->hasDocument($document)) {
            return;
        }

        // see if we can resolve the corresponding node in the PDM.
        // if we cannot then we either return and let the document
        // manager create the new node, or, if $create is true, create
        // the missing node (this happens when registering a document
        // which is a relation to the incoming DDM document).
        if (false === $uuid = $this->resolvePDMUUID($document)) {
            if (false === $create) {
                return;
            }

            $this->publishManager->getNodeManager()->createPath(
                $ddmInspector->getPath($document), $ddmInspector->getUuid($document)
            );

            return;
        }

        // register the DDM document against the PDM PHPCR node.
        $node = $this->publishManager->getNodeManager()->find($uuid);
        $locale = $ddmInspector->getLocale($document);
        $pdmRegistry->registerDocument(
            $document,
            $node,
            $locale
        );
    }

    /**
     * If possible, resolve the UUID of the node in the PDM corresponding to
     * the DDM node.
     *
     * If the UUID does not exist, we check to see if the path exsits.
     * if neither the path or UUID exist, then the PDM should create a new
     * document and we ensure that the PARENT path exists and if it doesn't
     * we syncronize the ancestor nodes from the DDM.
     *
     * We return FALSE in the case that a new document should be created.
     *
     * In the case the UUID does not exist we assume that in a valid system
     * that path will also NOT EXIST. If the path does exist, then it means that
     * the corresponding PHPCR nodes were created independently of each
     * other and bypassed the syncrhonization system and we throw an exception.
     *
     * @throws \RuntimeException If the UUID could not be resolved and it would be
     *                           invalid to implicitly allow the node to be created.
     */
    private function resolvePDMUUID($object)
    {
        $pdmNodeManager = $this->publishManager->getNodeManager();
        $ddmInspector = $this->defaultManager->getInspector();
        $uuid = $ddmInspector->getUUid($object);

        if (true === $pdmNodeManager->has($uuid)) {
            return $uuid;
        }

        $path = $ddmInspector->getPath($object);

        if (false === $pdmNodeManager->has($path)) {

            // if the parent path also does not exist in the PDM then we need
            // to create the parent path using the same UUIDs that are used in
            // the DDM.
            $parentPath = PathHelper::getParentPath($path);
            if (false === $pdmNodeManager->has($parentPath)) {
                $this->syncPDMPath($parentPath);

                return false;
            }

            return false;
        }

        throw new \RuntimeException(sprintf(
            'Publish document manager already has a node at path "%s" but ' .
            'incoming UUID `%s` does not match existing UUID: "%s".',
            $path, $uuid, $pdmNodeManager->find($path)->getIdentifier()
        ));
    }

    /**
     * Sync the given path from the DDM to the PDM, preserving
     * the UUIDs.
     *
     * @param string $path
     */
    private function syncPDMPath($path)
    {
        $ddmNodeManager = $this->defaultManager->getNodeManager();
        $pdmNodeManager = $this->publishManager->getNodeManager();
        $segments = explode('/', $path);
        $stack = [];

        foreach ($segments as $segment) {
            $stack[] = $segment;

            if ($segment == '') {
                continue;
            }

            $path = implode('/', $stack) ?: '/';
            $ddmNode = $ddmNodeManager->find($path);
            $pdmNodeManager->createPath($path, $ddmNode->getIdentifier());
        }

        // jackalope, at time of writing, will not register the UUID against
        // the node when the UUID is set on a property, meaning that we cannot
        // reference it by UUID until the session is flushed.
        //
        // upstream fix: https://github.com/jackalope/jackalope/pull/307
        $pdmNodeManager->save();
    }
}
