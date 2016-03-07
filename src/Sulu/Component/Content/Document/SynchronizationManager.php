<?php

namespace Sulu\Component\Content\Document;

use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\DocumentManagerRegistryInterface;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentManagerRegistry;
use Sulu\Component\Content\Document\Behavior\SynchronizeBehavior;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;

/**
 * The synchronization manager handles the synchronization of documents
 * from the DEFAULT document manger to the PUBLISH document manager.
 *
 * NOTE: In the future multiple document managers may be supported.
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

    public function __construct(
        DocumentManagerRegistry $registry,
        PropertyEncoder $encoder,
        $publishManagerName
    )
    {
        $this->registry = $registry;
        $this->publishManagerName = $publishManagerName;
        $this->encoder = $encoder;
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
    public function synchronizeFull(SynchronizeBehavior $document, $force = false)
    {
        // get the default managerj
        $defaultManager = $this->registry->getManager();
        $publishManager = $this->getPublishDocumentManager();

        // if the  emitting manager is the publish manager, stop we
        // don't want to sync from the publish workspace!
        //
        // TODO: this should be removed by implementing per-document-manager
        //       event-subscribers.
        if ($publishManager === $defaultManager) {
            return;
        }

        // Get the routes for the document if it implements
        // the "resource segment subscriber".
        //
        // TODO: Fire an event here instead?
        $routes = [];
        if ($document instanceof ResourceSegmentBehavior) {
            $routes = $this->getDocumentRoutes($defaultManager->getInspector(), $document);
        }

        $toSynchronize = array_merge([
            $document,
        ], $routes);

        foreach ($toSynchronize as $syncDocument) {
            $this->synchronizeSingle($syncDocument, $force);
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
     * @param boolean $force
     */
    public function synchronizeSingle(SynchronizeBehavior $document, $force = false)
    {
        $defaultManager = $this->registry->getManager();
        $publishManager = $this->registry->getManager($this->publishManagerName);

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
        if (false === $force && in_array($this->publishManagerName, $synced)) {
            return;
        }

        $inspector = $defaultManager->getInspector();
        $locale = $inspector->getLocale($document);
        $path = $inspector->getPath($document);

        // register the DDM document and its immediate relations with the PDM
        // PHPCR node.
        $this->registerDocumentWithPDM($document);

        // this is a temporary (and invalid) hack until the routing system
        // is converted to use the document manager.
        if ($document instanceof ResourceSegmentBehavior) {
            $document->setResourceSegment('/' . uniqid());
        }

        // as we are explicitly setting the path we can discard any parent document
        // which may not be registered in the publish manager.
        if ($document instanceof ParentBehavior) {
            $document->setParent(null);
        }

        // TODO: What about other proxy situations? i.e. children and
        //       referrers. Can we remove proxies automatically? or should we
        //       cascade?

        // save the document with the "publish" document manager.
        $publishManager->persist(
            $document,
            $locale,
            [
                'path' => $path,
                'auto_create' => true
            ]
        );
        // the document is now synchronized with the publish workspace...

        // add the document manager name to the list of synchronized
        // document managers directly on the PHPCR node.
        //
        // NOTE: why do we store an array instead of a boolean? (i.e. we only
        //       have one synchronization target) - we are supporting the possiblity
        //       that there MIGHT be more than one synchronization target.
        $synced[] = $this->publishManagerName;
        $node = $inspector->getNode($document);
        $node->setProperty(
            $this->encoder->localizedSystemName(
                SynchronizeBehavior::SYNCED_FIELD,
                $inspector->getLocale($document)
            ),
            array_unique($synced)
        );
    }

    /**
     * Return routes related to the document.
     *
     * @param DocumentInspector
     * @param object $document
     */
    private function getDocumentRoutes(DocumentInspector $inspector, ResourceSegmentBehavior $document)
    {
        $referrers = $inspector->getReferrers($document);
        $routes = array();

        foreach ($referrers as $referrer) {
            if (!$referrer instanceof RouteDocument) {
                continue;
            }

            // this clause is technically not required as we already know that
            // it is a RouteDocument, but it adds another layer of safety.
            if (!$referrer instanceof SynchronizeBehavior) {
                throw new \RuntimeException(sprintf(
                    'All route classes must implement the SynchronizeBehavior, for "%s"'
                , get_class($referrer)));
            }

            // if the route is already synchronized, continue.
            $synced = $referrer->getSynchronizedManagers() ?: [];
            if (in_array($this->publishManagerName, $synced)) {
                continue;
            }

            if ($referrer instanceof ParentBehavior) {
                $referrer->setParent(null);
            }

            $routes[] = $referrer;
        }

        return $routes;
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
     * @param SynchronizeBehavior $document
     */
    private function registerDocumentWithPDM(SynchronizeBehavior $document)
    {
        $this->registerSingleDocumentWithPDM($document);

        $defaultManager = $this->registry->getManager();
        $metadata = $defaultManager->getMetadataFactory()->getMetadataForClass(get_class($document));
        $reflectionClass = $metadata->getReflectionClass();

        foreach (array_keys($metadata->getFieldMappings()) as $field) {
            $reflectionProperty = $reflectionClass->getProperty($field);
            $reflectionProperty->setAccessible(true);
            $propertyValue = $reflectionProperty->getValue($document);

            if (false === is_object($propertyValue)) {
                continue;
            }

            $this->registerSingleDocumentWithPDM($propertyValue);
        }
    }

    private function registerSingleDocumentWithPDM($object)
    {
        $publishManager = $this->getPublishDocumentManager();
        $defaultManager = $this->registry->getManager();
        $ddmInspector = $defaultManager->getInspector();

        // if the default document manager does not have this object then it is
        // not a candidate for being persisted (e.g. it might be a \DateTime
        // object).
        if (false === $defaultManager->getRegistry()->hasDocument($object)) {
            return;
        }
        $uuid = $ddmInspector->getUUid($object);
        $locale = $ddmInspector->getLocale($object);
        $pdmRegistry = $publishManager->getRegistry();

        // if the PDM registry already has the document, then there is
        // nothing to do.
        if (true === $pdmRegistry->hasDocument($object)) {
            return;
        }

        // If the PDM PHPCR session does not have the node, then there is
        // nothing to do.
        if (false === $publishManager->getNodeManager()->has($uuid)) {
            return;
        }

        // register the DDM document with the PDM PHPCR node.
        $node = $publishManager->getNodeManager()->find($uuid);
        $pdmRegistry->registerDocument(
            $object,
            $node,
            $locale
        );
    }
}
