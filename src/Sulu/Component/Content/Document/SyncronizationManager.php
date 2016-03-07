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
use Sulu\Component\Content\Document\Behavior\SyncronizeBehavior;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;

/**
 * The syncronization manager handles the syncronization of documents
 * from the DEFAULT document manger to the PUBLISH document manager.
 *
 * NOTE: In the future multiple document managers may be supported.
 */
class SyncronizationManager
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
     * NOTE: This is used only by the syncronization subscriber in order
     *       to "flush" the PDM.
     *
     * @return DocumentManagerInterface
     */
    public function getPublishDocumentManager()
    {
        return $this->registry->getManager($this->publishManagerName);
    }

    /**
     * Syncronize a document and any other documents which are
     * associated with it and should also be published.
     *
     * All document managers involved will be FLUSHED after
     * the operation has completed.
     *
     * @param SyncronizeBehavior $document
     */
    public function syncronizeFull(SyncronizeBehavior $document, $force = false)
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

        $toSyncronize = array_merge([
            $document,
        ], $routes);

        foreach ($toSyncronize as $syncDocument) {
            $this->syncronizeSingle($syncDocument, $force);
        }

        $publishManager->flush();
        $defaultManager->flush();
    }

    /**
     * Syncronize a single document to the publish document manager in the
     * documents currently registered locale.
     *
     * FLUSH will not be called and no associated documents will be
     * syncronized.
     *
     * TODO: Add an explicit "locale" option?
     *
     * @param SyncronizeBehavior $document
     * @param boolean $force
     */
    public function syncronizeSingle(SyncronizeBehavior $document, $force = false)
    {
        $defaultManager = $this->registry->getManager();
        $publishManager = $this->registry->getManager($this->publishManagerName);

        // see comment in same condition above.
        if ($publishManager === $defaultManager) {
            return;
        }

        // get the list of managers with which the document is already
        // syncronized (the value may be NULL as we have no control over what
        // the user does with this mapped value).
        $synced = $document->getSyncronizedManagers() ?: [];

        // unless forced, we will not process documents which are already
        // synced with the publish document manager.
        if (false === $force && in_array($this->publishManagerName, $synced)) {
            return;
        }

        $inspector = $defaultManager->getInspector();
        $locale = $inspector->getLocale($document);
        $path = $inspector->getPath($document);
        $uuid = $inspector->getUuid($document);

        $registry = $publishManager->getRegistry();
        // if the publish manager already has the incoming PHPCR node then we
        // need to register the existing PHPCR node from the publish session
        // with the incoming document (otherwise the system will attempt to
        // create a new document and fail).
        if (false === $registry->hasDocument($document)) {
            if ($publishManager->getNodeManager()->has($uuid)) {
                $node = $publishManager->getNodeManager()->find($uuid);

                $registry->registerDocument(
                    $document,
                    $node,
                    $locale
                );
            }
        }

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
        // the document is now syncronized with the publish workspace...

        // add the document manager name to the list of syncronized
        // document managers directly on the PHPCR node.
        //
        // NOTE: why do we store an array instead of a boolean? (i.e. we only
        //       have one syncronization target) - we are supporting the possiblity
        //       that there MIGHT be more than one syncronization target.
        $synced[] = $this->publishManagerName;
        $node = $inspector->getNode($document);
        $node->setProperty(
            $this->encoder->localizedSystemName(
                SyncronizeBehavior::SYNCED_FIELD,
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
            if (!$referrer instanceof SyncronizeBehavior) {
                throw new \RuntimeException(sprintf(
                    'All route classes must implement the SyncronizeBehavior, for "%s"'
                , get_class($referrer)));
            }

            // if the route is already syncronized, continue.
            if (in_array($this->publishManagerName, $referrer->getSyncronizedManagers())) {
                continue;
            }

            $routes[] = $referrer;
        }

        return $routes;
    }
}
