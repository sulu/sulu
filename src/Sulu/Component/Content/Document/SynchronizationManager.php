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
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\ClassNameInflector;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use PHPCR\Util\PathHelper;

/**
 * The synchronization manager handles the synchronization of documents
 * from the DEFAULT document manger to the PUBLISH document manager.
 *
 * NOTE: In the future multiple document managers may be supported.
 *
 * NOTE: Much of the logic in this class is about ensuring that corresponding
 *       nodes exist in the PDM - this could be simplified (and made much more
 *       efficient) if we were to use Jackalope observation to implement Event
 *       listeners at the PHPCR level, however this feature is currently not
 *       supported.
 *
 *       See: https://github.com/jackalope/jackalope/pull/241
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
    public function synchronizeFull(SynchronizeBehavior $document, array $options = array())
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
     * @param boolean $force
     */
    public function synchronizeSingle(SynchronizeBehavior $document, array $options = array())
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
        $this->registerDocumentWithPDM($document);

        // this is a temporary (and invalid) hack until the routing system
        // is converted to use the document manager.
        if ($document instanceof ResourceSegmentBehavior) {
            $document->setResourceSegment('/' . uniqid());
        }

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
        $encoding = $document instanceof LocaleBehavior ? 'localizedSystemName' : 'systemName';
        $node->setProperty(
            $this->encoder->$encoding(
                SynchronizeBehavior::SYNCED_FIELD,
                $inspector->getLocale($document)
            ),
            array_unique($synced)
        );
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
    private function registerDocumentWithPDM(SynchronizeBehavior $document)
    {
        $this->registerSingleDocumentWithPDM($document);

        $defaultManager = $this->registry->getManager();
        $metadata = $defaultManager->getMetadataFactory()->getMetadataForClass(get_class($document));
        $reflectionClass = $metadata->getReflectionClass();

        // iterate over the field mappings for the document, if they resolve to
        // an object then try and register it with the PDM.
        foreach (array_keys($metadata->getFieldMappings()) as $field) {
            $reflectionProperty = $reflectionClass->getProperty($field);
            $reflectionProperty->setAccessible(true);
            $propertyValue = $reflectionProperty->getValue($document);

            if (false === is_object($propertyValue)) {
                continue;
            }

            // if the default document manager does not have this object then it is
            // not a candidate for being persisted (e.g. it might be a \DateTime
            // object).
            if (false === $defaultManager->getRegistry()->hasDocument($propertyValue)) {
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

    private function registerSingleDocumentWithPDM($object, $create = false)
    {
        $publishManager = $this->getPublishDocumentManager();
        $defaultManager = $this->registry->getManager();
        $ddmInspector = $defaultManager->getInspector();
        $pdmRegistry = $publishManager->getRegistry();

        // if the PDM registry already has the document, then
        // there is nothing to do - the document manager will
        // handle the rest.
        if (true === $pdmRegistry->hasDocument($object)) {
            return;
        }

        $locale = $ddmInspector->getLocale($object);

        // see if we can resolve the corresponding node in the PDM.
        // if we cannot then we either return and let the document
        // manager create the new node, or, if $create is true, create
        // the missing node (this happens when registering a document
        // which is a relation to the incoming DDM document).
        if (false === $uuid = $this->resolvePDMUUID($object)) {
            if (false === $create) {
                return;
            }

            $publishManager->getNodeManager()->createPath($ddmInspector->getPath($object), $ddmInspector->getUuid($object));

            return;
        }

        // register the DDM document against the PDM PHPCR node.
        $node = $publishManager->getNodeManager()->find($uuid);
        $pdmRegistry->registerDocument(
            $object,
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
        $pdmNodeManager = $this->getPublishDocumentManager()->getNodeManager();
        $defaultManager = $this->registry->getManager();
        $ddmInspector = $defaultManager->getInspector();
        $uuid = $ddmInspector->getUUid($object);
        $path = $ddmInspector->getPath($object);

        if (true === $pdmNodeManager->has($uuid)) {
            return $uuid;
        }

        if (false === $pdmNodeManager->has($path)) {

            // if the parent path also does not exist in the PDM then we need
            // to create the parent path using the same UUIDs that are used in
            // the DDM.
            $parentPath = PathHelper::getParentPath($path);
            if (false === $pdmNodeManager->has($parentPath)) {
                $this->syncPDMPath($parentPath);
                return false;
            }

            // otherwise we can safely create the document.
            return false;
        }

        throw new \RuntimeException(sprintf(
            'Publish document manager already has a node at path "%s" but ' .
            'incoming UUID `%s` does not match existing UUID: "%s".',
            $path, $uuid, $pdmNodeManager->find($path)->getIdentifier()
        ));
    }

    /**
     * Return routes related to the document.
     *
     * See caller TODO.
     *
     * @param DocumentInspector
     * @param ResourceSegmentBehavior $document
     */
    private function getDocumentRoutes(DocumentInspector $inspector, ResourceSegmentBehavior $document)
    {
        $referrers = $inspector->getReferrers($document);
        $routes = array();

        foreach ($referrers as $referrer) {
            if (!$referrer instanceof RouteDocument) {
                continue;
            }
            $routes[] = $referrer;
        }

        return $routes;
    }

    /**
     * Sync the given path from the DDM to the PDM, preserving
     * the UUIDs.
     *
     * @param string $path
     */
    private function syncPDMPath($path)
    {
        $ddmNodeManager = $this->registry->getManager()->getNodeManager();
        $pdmNodeManager = $this->getPublishDocumentManager()->getNodeManager();
        $segments = explode('/', $path);
        $stack = [];

        foreach ($segments as $segment) {
            $stack[] = $segment;
            $path = implode('/', $stack) ?: '/';
            $ddmNode = $ddmNodeManager->find($path);
            $pdmNodeManager->createPath($path, $ddmNode->getIdentifier());
        }

        // flush the PHPCR session. if we do not save() here, then the node
        // manager will be unable to "find" the newly created nodes by ID
        // within the same session - because Jackalope.
        //
        // TODO: create an issue for this.
        $pdmNodeManager->save();
    }
}
