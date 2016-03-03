<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document;

use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Component\Content\Document\Syncronization\DocumentRegistrator;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\DocumentManagerRegistryInterface;

/**
 * The synchronization manager handles the synchronization of documents
 * from the DEFAULT document manger to the PUBLISH document manager.
 *
 * NOTE: In the future multiple document managers may be supported.
 *
 * TODO: Explcitly inject publish and default document managers?
 */
class SynchronizationManager
{
    /**
     * @var DocumentManagerRegistryInterface
     */
    private $registry;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var string
     */
    private $publishManagerName;

    /**
     * @var DocumentRegistrator
     */
    private $registrator;

    public function __construct(
        DocumentManagerRegistry $registry,
        PropertyEncoder $encoder,
        $publishManagerName,
        $registrator = null
    ) {
        $this->registry = $registry;
        $this->publishManagerName = $publishManagerName;
        $this->encoder = $encoder;
        $this->registrator = $registrator ?: new DocumentRegistrator(
            $registry->getManager(),
            $registry->getManager($this->publishManagerName)
        );
    }

    /**
     * Return the publish document manager (PDM).
     *
     * This should be the only class that is aware of the PDM name. By having
     * this method we can be sure that whatever the PDM is, the PDM is always
     * the PDM.
     *
     * NOTE: This is used only by the synchronization subscriber in order
     *       to "flush" the PDM.
     *
     * @return DocumentManagerInterface
     */
    public function getPublishDocumentManager()
    {
        return $this->registry->getManager($this->publishManagerName);
    }

    /**
     * Synchronize a document and any other documents which are
     * associated with it and should also be published.
     *
     * All document managers involved will be FLUSHED after
     * the operation has completed.
     *
     * @param SynchronizeBehavior $document
     */
    public function synchronizeFull(SynchronizeBehavior $document, array $options = [])
    {
        // get the default managerj
        $defaultManager = $this->registry->getManager();
        $publishManager = $this->getPublishDocumentManager();

        // if the  emitting manager is the publish manager, stop we
        // don't want to sync from the publish workspace!
        if ($publishManager === $defaultManager) {
            return;
        }

        // Get the routes for the document if it implements
        // the "resource segment subscriber".
        //
        // TODO: We should instead add configuration to cascade certain
        //       document classes instead of hard coding this logic.
        $routes = [];
        if ($document instanceof ResourceSegmentBehavior) {
            $routes = $this->getDocumentRoutes($defaultManager->getInspector(), $document);
        }

        $toSynchronize = array_merge([
            $document,
        ], $routes);

        foreach ($toSynchronize as $syncDocument) {
            $this->synchronizeSingle($syncDocument, $options);
        }

        $publishManager->flush();
        $defaultManager->flush();
    }

    /**
     * Synchronize a single document to the publish document manager in the
     * documents currently registered locale.
     *
     * FLUSH will not be called and no associated documents will be
     * synchronized.
     *
     * TODO: Add an explicit "locale" option?
     *
     * @param SynchronizeBehavior $document
     * @param bool $force
     */
    public function synchronizeSingle(SynchronizeBehavior $document, array $options = [])
    {
        $options = array_merge([
            'force' => false,
        ], $options);

        $defaultManager = $this->registry->getManager();
        $publishManager = $this->getPublishDocumentManager();

        // see comment in same condition above.
        if ($publishManager === $defaultManager) {
            return;
        }

        // get the list of managers with which the document is already
        // synchronized (the value may be NULL as we have no control over what
        // the user does with this mapped value).
        $synced = $document->getSynchronizedManagers() ?: [];

        // unless forced, we will not process documents which are already
        // synced with the publish document manager.
        if (false === $options['force'] && in_array($this->publishManagerName, $synced)) {
            return;
        }

        $inspector = $defaultManager->getInspector();
        $locale = $inspector->getLocale($document);
        $path = $inspector->getPath($document);

        // register the DDM document and its immediate relations with the PDM
        // PHPCR node.
        $this->registrator->registerDocumentWithPDM($document);

        // save the document with the "publish" document manager.
        $publishManager->persist(
            $document,
            $locale,
            [
                'path' => $path,
            ]
        );
        // the document is now synchronized with the publish workspace...

        // add the document manager name to the list of synchronized
        // document managers directly on the PHPCR node.
        //
        // we store an array instead of a boolean because we are supporting the
        // possiblity that there MAY one day be more than one synchronization
        // target.
        //
        // TODO: We should should set this value on the document and re-persist
        //       it rather than leak localization behavior here, however this is
        //       currently a heavy operation due to the content system and lack of a
        //       UOW.
        $synced[] = $this->publishManagerName;
        $node = $inspector->getNode($document);

        if ($document instanceof LocaleBehavior) {
            $node->setProperty(
                $this->encoder->localizedSystemName(
                    SynchronizeBehavior::SYNCED_FIELD,
                    $inspector->getLocale($document)
                ),
                array_unique($synced)
            );
        } else {
            $node->setProperty(
                $this->encoder->systemName(
                    SynchronizeBehavior::SYNCED_FIELD
                ),
                array_unique($synced)
            );
        }
    }

    /**
     * Return routes related to the document.
     *
     * @param DocumentInspector
     * @param ResourceSegmentBehavior $document
     */
    private function getDocumentRoutes(DocumentInspector $inspector, ResourceSegmentBehavior $document)
    {
        $referrers = $inspector->getReferrers($document);
        $routes = [];

        foreach ($referrers as $referrer) {
            if (!$referrer instanceof RouteDocument) {
                continue;
            }
            $routes[] = $referrer;
        }

        return $routes;
    }
}
