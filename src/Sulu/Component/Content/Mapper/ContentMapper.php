<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Jackalope\Query\Row;
use PHPCR\NodeInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\Util\PathHelper;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\BreadcrumbItem;
use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\Structure as LegacyStructure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\OrderBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\ShadowLocaleBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\Content\Document\LocalizationState;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Exception\InvalidOrderPositionException;
use Sulu\Component\Content\Exception\TranslatedNodeNotFoundException;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Mapper\Event\ContentNodeEvent;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Maps content nodes to phpcr nodes with content types and provides utility function to handle content nodes.
 *
 * @deprecated since 1.0-? use the DocumentManager instead.
 */
class ContentMapper implements ContentMapperInterface
{
    /**
     * @var ContentTypeManager
     */
    private $contentTypeManager;

    /**
     * @var ExtensionManager
     */
    private $structureManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var Cache
     */
    private $extensionDataCache;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * @Var DocumentManager
     */
    private $documentManager;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var PropertyEncoder
     */
    private $encoder;

    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    public function __construct(
        DocumentManager $documentManager,
        WebspaceManagerInterface $webspaceManager,
        FormFactoryInterface $formFactory,
        DocumentInspector $inspector,
        PropertyEncoder $encoder,
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        SessionManagerInterface $sessionManager,
        EventDispatcherInterface $eventDispatcher,
        RlpStrategyInterface $strategy,
        NamespaceRegistry $namespaceRegistry
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->sessionManager = $sessionManager;
        $this->webspaceManager = $webspaceManager;
        $this->documentManager = $documentManager;
        $this->formFactory = $formFactory;
        $this->inspector = $inspector;
        $this->encoder = $encoder;
        $this->namespaceRegistry = $namespaceRegistry;

        // deprecated
        $this->eventDispatcher = $eventDispatcher;
        $this->strategy = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function saveRequest(ContentMapperRequest $request)
    {
        return $this->save(
            $request->getData(),
            $request->getTemplateKey(),
            $request->getWebspaceKey(),
            $request->getLocale(),
            $request->getUserId(),
            $request->getPartialUpdate(),
            $request->getUuid(),
            $request->getParentUuid(),
            $request->getState(),
            $request->getIsShadow(),
            $request->getShadowBaseLanguage(),
            $request->getType()
        );
    }

    /**
     * @deprecated
     *
     * {@inheritdoc}
     */
    public function save(
        $data,
        $structureType,
        $webspaceKey,
        $locale,
        $userId,
        $partialUpdate = true, // ignore missing property: clearMissing = false
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null,
        $documentAlias = LegacyStructure::TYPE_PAGE
    ) {
        // map explicit arguments to data
        $data['parent'] = $parentUuid;
        $data['workflowStage'] = $state;
        $data['structureType'] = $structureType;

        if ($isShadow) {
            $data['shadowLocaleEnabled'] = true;
        }

        if ($shadowBaseLanguage) {
            $data['shadowLocale'] = $shadowBaseLanguage;
        }

        if ($uuid) {
            $document = $this->documentManager->find(
                $uuid,
                $locale,
                ['type' => $documentAlias, 'load_ghost_content' => false]
            );
        } else {
            $document = $this->documentManager->create($documentAlias);
        }

        if (!$document instanceof StructureBehavior) {
            throw new \RuntimeException(sprintf(
                'The content mapper can only be used to save documents implementing the StructureBehavior interface, got: "%s"',
                get_class($document)
            ));
        }

        $options = [
            'clear_missing_content' => !$partialUpdate,
        ];

        // We eventually handle this from the controller, in which case we will not
        // have to deal with not knowing what sort of form we will have.
        if ($document instanceof WebspaceBehavior) {
            $options['webspace_key'] = $webspaceKey;
        }

        // disable csrf protection, since we can't produce a token, because the form is cached on the client
        $options['csrf_protection'] = false;

        $form = $this->formFactory->create($documentAlias, $document, $options);

        $clearMissing = false;
        $form->submit($data, $clearMissing);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->documentManager->persist($document, $locale, [
            'user' => $userId,
        ]);

        $this->documentManager->flush();

        $structure = $this->documentToStructure($document);

        $event = new ContentNodeEvent($this->inspector->getNode($document), $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_SAVE, $event);

        return $structure;
    }

    /**
     * TODO: Refactor this .. this should be handled in a listener or in the form, or something
     * {@inheritdoc}
     */
    public function saveExtension(
        $uuid,
        $data,
        $extensionName,
        $webspaceKey,
        $locale,
        $userId
    ) {
        $document = $this->loadDocument(
            $uuid,
            $locale,
            [
                'exclude_ghost' => true,
            ]
        );

        if ($document === null) {
            throw new TranslatedNodeNotFoundException($uuid, $locale);
        }

        if (!$document instanceof ExtensionBehavior) {
            throw new \RuntimeException(sprintf(
                'Document of class "%s" must implement the ExtensionableBehavior if it is to be extended',
                get_class($document)
            ));
        }

        // save data of extensions
        $extension = $this->structureManager->getExtension($document->getStructureType(), $extensionName);
        $node = $this->inspector->getNode($document);

        $extension->save($node, $data, $webspaceKey, $locale);
        $extensionData = $extension->load($node, $webspaceKey, $locale);

        $document->setExtension($extension->getName(), $extensionData);

        $this->documentManager->flush();

        $structure = $this->documentToStructure($document);

        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_SAVE, $event);

        return $structure;
    }

    public function loadByParent(
        $uuid,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $ignoreExceptions = false,
        $excludeGhosts = false
    ) {
        $parent = null;
        $options = ['load_ghost_content' => true];
        if ($uuid) {
            $parent = $this->documentManager->find($uuid, $languageCode, $options);
        }

        if (null === $parent) {
            $parent = $this->getContentDocument($webspaceKey, $languageCode, $options);
        }

        $fetchDepth = -1;

        if (false === $flat) {
            $fetchDepth = $depth;
        }

        $children = $this->inspector->getChildren($parent, $options);
        $children = $this->documentsToStructureCollection($children->toArray(), [
            'exclude_ghost' => $excludeGhosts,
        ]);

        if ($flat) {
            foreach ($children as $child) {
                if ($depth === null || $depth > 1) {
                    $childChildren = $this->loadByParent(
                        $child->getUuid(),
                        $webspaceKey,
                        $languageCode,
                        $depth - 1,
                        $flat,
                        $ignoreExceptions,
                        $excludeGhosts
                    );
                    $children = array_merge($children, $childChildren);
                }
            }
        }

        return $children;
    }

    /**
     * {@inheritdoc}
     */
    public function load($uuid, $webspaceKey, $locale, $loadGhostContent = false)
    {
        $document = $this->documentManager->find($uuid, $locale, [
            'load_ghost_content' => $loadGhostContent,
        ]);

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadStartPage($webspaceKey, $locale)
    {
        $startPage = $this->getContentDocument($webspaceKey, $locale);
        $startPage->setWorkflowStage(WorkflowStage::PUBLISHED);
        $startPage->setNavigationContexts([]);

        return $this->documentToStructure($startPage);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $locale, $segmentKey = null)
    {
        $uuid = $this->getResourceLocator()->loadContentNodeUuid(
            $resourceLocator,
            $webspaceKey,
            $locale,
            $segmentKey
        );

        $document = $this->loadDocument($uuid, $locale, [
            'exclude_shadow' => false,
        ]);

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadBySql2($sql2, $locale, $webspaceKey, $limit = null)
    {
        $query = $this->documentManager->createQuery($sql2, $locale);
        $query->setMaxResults($limit);

        $documents = $query->execute();

        return $this->documentsToStructureCollection($documents, null);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByQuery(
        QueryInterface $query,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $options = [
            'exclude_ghost' => $excludeGhost,
            'load_ghost_content' => $loadGhostContent,
        ];

        $documents = $this->documentManager->createQuery($query, $locale, $options)->execute();

        return $this->documentsToStructureCollection($documents, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function loadNodeAndAncestors(
        $uuid,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $excludeShadow = true
    ) {
        $document = $this->loadDocument(
            $uuid,
            $locale,
            $options = [
                'load_ghost_content' => true,
                'exclude_ghost' => $excludeGhost,
                'exclude_shadow' => $excludeShadow,
            ],
            false
        );

        if (null === $document) {
            return [];
        }

        $documents = [];
        if (!$this->optionsShouldExcludeDocument($document, $options)) {
            $documents[] = $document;
        }

        if ($document instanceof HomeDocument) {
            return $this->documentsToStructureCollection($documents, $options);
        }

        while ($document) {
            $parentDocument = $this->inspector->getParent($document);
            $documents[] = $parentDocument;
            if ($parentDocument instanceof HomeDocument) {
                return $this->documentsToStructureCollection($documents, $options);
            }
            $document = $parentDocument;
        }

        throw new \RuntimeException(sprintf(
            'Did not traverse an instance of HomeDocument when searching for desendants of document "%s"',
            $uuid
        ));
    }

    /**
     * Load/hydrate a shalow structure with the given node.
     * Shallow structures do not have content properties / extensions
     * hydrated.
     *
     * @param NodeInterface $node
     * @param string        $localization
     * @param string        $webspaceKey
     *
     * @return StructureInterface
     */
    public function loadShallowStructureByNode(NodeInterface $contentNode, $localization, $webspaceKey)
    {
        $document = $this->documentManager->find($contentNode->getPath(), $localization);

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByNode(
        NodeInterface $node,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false,
        $excludeShadow = true
    ) {
        $document = $this->loadDocument(
            $node->getIdentifier(),
            $locale,
            [
                'load_ghost_content' => $loadGhostContent,
                'exclude_ghost' => $excludeGhost,
                'exclude_shadow' => $excludeShadow,
            ]
        );

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function loadBreadcrumb($uuid, $locale, $webspaceKey)
    {
        $document = $this->documentManager->find($uuid, $locale);

        $documents = [];
        $contentDocument = $this->getContentDocument($webspaceKey, $locale);
        $contentDepth = $this->inspector->getDepth($contentDocument);
        $document = $this->inspector->getParent($document);
        $documentDepth = $this->inspector->getDepth($document);

        while ($document instanceof StructureBehavior && $documentDepth >= $contentDepth) {
            $documents[] = $document;

            $document = $this->inspector->getParent($document);
            $documentDepth = $this->inspector->getDepth($document);
        }

        $items = [];
        foreach ($documents as $document) {
            $items[] = new BreadcrumbItem(
                $this->inspector->getDepth($document) - $contentDepth,
                $this->inspector->getUuid($document),
                $document->getTitle()
            );
        }

        $items = array_reverse($items);

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($uuid, $webspaceKey, $dereference = false)
    {
        $document = $this->documentManager->find($uuid);
        $this->documentManager->remove($document, $dereference);
        $this->documentManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function move($uuid, $destParentUuid, $userId, $webspaceKey, $locale)
    {
        return $this->copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($uuid, $destParentUuid, $userId, $webspaceKey, $locale)
    {
        return $this->copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $locale, false);
    }

    /**
     * {@inheritdoc}
     */
    public function copyLanguage(
        $uuid,
        $userId,
        $webspaceKey,
        $srcLocale,
        $destLocales,
        $structureType = LegacyStructure::TYPE_PAGE
    ) {
        if (!is_array($destLocales)) {
            $destLocales = [$destLocales];
        }

        $document = $this->documentManager->find($uuid, $srcLocale);
        $parentDocument = $this->inspector->getParent($document);

        $resourceLocatorType = $this->getResourceLocator();

        foreach ($destLocales as $destLocale) {
            $document->setLocale($destLocale);
            $document->getStructure()->bind($document->getStructure()->toArray());

            // TODO: This can be removed if RoutingAuto replaces the ResourceLocator code.
            if ($document instanceof ResourceSegmentBehavior) {
                $parentResourceLocator = $resourceLocatorType->getResourceLocatorByUuid(
                    $parentDocument->getUUid(),
                    $webspaceKey,
                    $destLocale
                );
                $resourceLocator = $resourceLocatorType->getStrategy()->generate(
                    $document->getTitle(),
                    $parentResourceLocator,
                    $webspaceKey,
                    $destLocale
                );

                $document->setResourceSegment($resourceLocator);
            }

            $this->documentManager->persist($document, $destLocale);
        }
        $this->documentManager->flush();

        return $this->documentToStructure($document);
    }

    /**
     * {@inheritdoc}
     */
    public function orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $locale)
    {
        $document = $this->documentManager->find($uuid, $locale);
        $this->documentManager->reorder($document, $beforeUuid);
        $this->documentManager->persist($document, $locale, [
            'user' => $userId,
        ]);

        return $this->documentToStructure($document);
    }

    /**
     * TODO: Move this logic to the DocumentManager
     * {@inheritdoc}
     */
    public function orderAt($uuid, $position, $userId, $webspaceKey, $locale)
    {
        $document = $this->documentManager->find($uuid, $locale);

        $parentDocument = $this->inspector->getParent($document);
        $siblingDocuments = $this->inspector->getChildren($parentDocument);

        $siblings = array_values($siblingDocuments->toArray()); // get indexed array
        $countSiblings = count($siblings);
        $currentPosition = array_search($document, $siblings) + 1;

        if ($countSiblings < $position || $position <= 0) {
            throw new InvalidOrderPositionException(sprintf(
                'Cannot order node "%s" at out-of-range position "%s", must be >= 0 && < %d"',
                $this->inspector->getPath($document),
                $position,
                $countSiblings
            ));
        }

        if ($position === $countSiblings) {
            // move to the end
            $this->documentManager->reorder($document, null);
        } else {
            if ($currentPosition < $position) {
                $targetSibling = $siblings[$position];
            } elseif ($currentPosition > $position) {
                $targetSibling = $siblings[$position - 1];
            }

            $this->documentManager->reorder($document, $targetSibling->getPath());
        }

        // this should not be necessary (see https://github.com/sulu-io/sulu-document-manager/issues/39)
        foreach ($siblingDocuments as $siblingDocument) {
            $this->documentManager->persist($siblingDocument, $locale);
        }

        $this->documentManager->flush();

        return $this->documentToStructure($document);
    }

    /**
     * Copy or move a node by UUID to a detination parent.
     *
     * Note that the bulk of this method is about the resource locator and can
     * be removed if we integrate the RoutingAuto component.
     *
     * @param string $webspaceKey
     * @param string $locale
     * @param bool $move
     *
     * @return StructureInterface
     */
    private function copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $locale, $move = true)
    {
        // find localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $localizations = $webspace->getAllLocalizations();

        // load from phpcr
        $document = $this->documentManager->find($uuid, $locale);
        $parentDocument = $this->documentManager->find($destParentUuid, $locale);

        if ($move) {
            // move node
            $this->documentManager->move($document, $destParentUuid);
        } else {
            // copy node
            $copiedPath = $this->documentManager->copy($document, $destParentUuid);
            $document = $this->documentManager->find($copiedPath, $locale);
            $this->documentManager->refresh($parentDocument);
        }

        $originalLocale = $locale;

        // modifiy the resource locators -- note this can be removed once the routing auto
        // system is implemented.
        foreach ($localizations as $locale) {
            $locale = $locale->getLocalization();

            if (!$document instanceof ResourceSegmentBehavior) {
                break;
            }

            // prepare parent content node
            // finding the document will update the locale without reloading from PHPCR
            $this->documentManager->find($document->getUuid(), $locale);
            $this->documentManager->find($parentDocument->getUuid(), $locale);

            $parentResourceLocator = '/';
            if ($parentDocument instanceof ResourceSegmentBehavior) {
                $parentResourceLocator = $parentDocument->getResourceSegment();
            }

            // TODO: This could be optimized
            $localizationState = $this->inspector->getLocalizationState($document);
            if ($localizationState !== LocalizationState::LOCALIZED) {
                continue;
            }

            if ($document->getRedirectType() !== RedirectType::NONE) {
                continue;
            }

            $strategy = $this->getResourceLocator()->getStrategy();
            $nodeName = PathHelper::getNodeName($document->getResourceSegment());
            $newResourceLocator = $strategy->generate(
                $nodeName,
                $parentDocument->getResourceSegment(),
                $webspaceKey,
                $locale
            );

            $document->setResourceSegment($newResourceLocator);

            $this->documentManager->persist($document, $locale, [
                'user' => $userId,
            ]);
        }

        $this->documentManager->flush();

        //
        $this->documentManager->find($document->getUuid(), $originalLocale);

        return $this->documentToStructure($document);
    }

    /**
     * Return the resource locator content type.
     *
     * @return ResourceLocatorInterface
     */
    public function getResourceLocator()
    {
        return $this->contentTypeManager->get('resource_locator');
    }

    /**
     * Return the content document (aka the home page).
     *
     * @param $webspaceKey
     *
     * @return Document
     */
    private function getContentDocument($webspaceKey, $locale, array $options = [])
    {
        return $this->documentManager->find(
            $this->sessionManager->getContentPath($webspaceKey),
            $locale,
            $options
        );
    }

    /**
     * Return the node in the content repository which contains all of the routes.
     *
     * @param $webspaceKey
     * @param string $locale
     * @param string $segment
     *
     * @return NodeInterface
     */
    protected function getRootRouteNode($webspaceKey, $locale, $segment)
    {
        return $this->documentManager->find(
            $this->sessionManager->getRoutePath($webspaceKey, $locale, $segment)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function convertQueryResultToArray(
        QueryResultInterface $queryResult,
        $webspaceKey,
        $locales,
        $fields,
        $maxDepth
    ) {
        $rootDepth = substr_count($this->sessionManager->getContentPath($webspaceKey), '/');

        $result = [];
        foreach ($locales as $locale) {
            foreach ($queryResult->getRows() as $row) {
                $pageDepth = substr_count($row->getPath('page'), '/') - $rootDepth;

                if ($maxDepth === null || $maxDepth < 0 || ($maxDepth > 0 && $pageDepth <= $maxDepth)) {
                    $item = $this->rowToArray($row, $locale, $webspaceKey, $fields);

                    if (false === $item || in_array($item, $result)) {
                        continue;
                    }

                    $result[] = $item;
                }
            };
        }

        return $result;
    }

    /**
     * @param $name
     * @param NodeInterface $parent
     *
     * @return string
     */
    private function getUniquePath($name, NodeInterface $parent)
    {
        if ($parent->hasNode($name)) {
            $i = 0;
            do {
                ++$i;
            } while ($parent->hasNode($name . '-' . $i));

            return $name . '-' . $i;
        } else {
            return $name;
        }
    }

    /**
     * converts a query row to an array.
     */
    private function rowToArray(Row $row, $locale, $webspaceKey, $fields)
    {
        // reset cache
        $this->initializeExtensionCache();
        $templateName = $this->encoder->localizedSystemName('template', $locale);
        $nodeTypeName = $this->encoder->localizedSystemName('nodeType', $locale);

        // check and determine shadow-nodes
        $node = $row->getNode('page');
        $document = $this->documentManager->find($node->getIdentifier(), $locale);
        $originalDocument = $document;

        if (!$node->hasProperty($templateName) && !$node->hasProperty($nodeTypeName)) {
            return false;
        }

        $redirectType = $document->getRedirectType();

        if ($redirectType === RedirectType::INTERNAL) {
            $target = $document->getRedirectTarget();

            if ($target) {
                $url = $target->getResourceSegment();

                $document = $target;
                $node = $this->inspector->getNode($document);
            }
        }

        if ($redirectType === RedirectType::EXTERNAL) {
            $url = 'http://' . $document->getRedirectExternal();
        }

        $originLocale = $locale;
        if ($document instanceof ShadowLocaleBehavior) {
            $locale = $document->isShadowLocaleEnabled() ? $document->getShadowLocale() : $originLocale;
        }

        $nodeState = null;
        if ($document instanceof WorkflowStageBehavior) {
            $nodeState = $document->getWorkflowStage();
        }

        // if page is not piblished ignore it
        if ($nodeState !== WorkflowStage::PUBLISHED) {
            return false;
        }

        if (!isset($url)) {
            $url = $document->getResourceSegment();
        }

        if (false === $url) {
            return;
        }

        // generate field data
        $fieldsData = $this->getFieldsData(
            $row,
            $node,
            $document,
            $fields[$originLocale],
            $document->getStructureType(),
            $webspaceKey,
            $locale
        );

        $structureType = $document->getStructureType();
        $shortPath = $this->inspector->getContentPath($originalDocument);

        $documentData = [
            'uuid' => $document->getUuid(),
            'nodeType' => $redirectType,
            'path' => $shortPath,
            'changed' => $document->getChanged(),
            'changer' => $document->getChanger(),
            'created' => $document->getCreated(),
            'published' => $document->getPublished(),
            'creator' => $document->getCreator(),
            'title' => $originalDocument->getTitle(),
            'url' => $url,
            'urls' => $this->inspector->getLocalizedUrlsForPage($document),
            'locale' => $locale,
            'webspaceKey' => $this->inspector->getWebspace($document),
            'template' => $structureType,
            'parent' => $this->inspector->getParent($document)->getUuid(),
        ];

        if ($document instanceof OrderBehavior) {
            $documentData['order'] = $document->getSuluOrder();
        }

        return array_merge($documentData, $fieldsData);
    }

    /**
     * Return extracted data (configured by fields array) from node.
     */
    private function getFieldsData(Row $row, NodeInterface $node, $document, $fields, $templateKey, $webspaceKey, $locale)
    {
        $fieldsData = [];
        foreach ($fields as $field) {
            // determine target for data in result array
            if (isset($field['target'])) {
                if (!isset($fieldsData[$field['target']])) {
                    $fieldsData[$field['target']] = [];
                }
                $target = &$fieldsData[$field['target']];
            } else {
                $target = &$fieldsData;
            }

            // create target
            if (!isset($target[$field['name']])) {
                $target[$field['name']] = '';
            }
            if (($data = $this->getFieldData($field, $row, $node, $document, $templateKey, $webspaceKey, $locale)) !== null) {
                $target[$field['name']] = $data;
            }
        }

        return $fieldsData;
    }

    /**
     * Return data for one field.
     */
    private function getFieldData($field, Row $row, NodeInterface $node, $document, $templateKey, $webspaceKey, $locale)
    {
        if (isset($field['column'])) {
            // normal data from node property
            return $row->getValue($field['column']);
        } elseif (isset($field['extension'])) {
            // data from extension
            return $this->getExtensionData(
                $node,
                $field['extension'],
                $field['property'],
                $webspaceKey,
                $locale
            );
        } elseif (
            isset($field['property'])
            && (!isset($field['templateKey']) || $field['templateKey'] === $templateKey)
        ) {
            // not extension data but property of node
            return $this->getPropertyData($document, $field['property']);
        }

        return;
    }

    /**
     * Returns data for property.
     */
    private function getPropertyData($document, LegacyProperty $property)
    {
        return $document->getStructure()->getContentViewProperty($property->getName())->getValue();
    }

    /**
     * Returns data for extension and property name.
     */
    private function getExtensionData(
        NodeInterface $node,
        ExtensionInterface $extension,
        $propertyName,
        $webspaceKey,
        $locale
    ) {
        // extension data: load ones
        if (!$this->extensionDataCache->contains($extension->getName())) {
            $this->extensionDataCache->save(
                $extension->getName(),
                $this->loadExtensionData(
                    $node,
                    $extension,
                    $webspaceKey,
                    $locale
                )
            );
        }

        // get extension data from cache
        $data = $this->extensionDataCache->fetch($extension->getName());

        // if property exists set it to target (with default value '')
        return isset($data[$propertyName]) ? $data[$propertyName] : null;
    }

    /**
     * load data from extension.
     */
    private function loadExtensionData(NodeInterface $node, ExtensionInterface $extension, $webspaceKey, $locale)
    {
        $extension->setLanguageCode($locale, $this->namespaceRegistry->getPrefix('extension_localized'), '');
        $data = $extension->load(
            $node,
            $webspaceKey,
            $locale
        );

        return $extension->getContentData($data);
    }

    /**
     * TODO: Move this to ResourceLocator repository>.
     *
     * {@inheritdoc}
     */
    public function restoreHistoryPath($path, $userId, $webspaceKey, $locale, $segmentKey = null)
    {
        $this->strategy->restoreByPath($path, $webspaceKey, $locale, $segmentKey);

        $content = $this->loadByResourceLocator($path, $webspaceKey, $locale, $segmentKey);
        $property = $content->getPropertyByTagName('sulu.rlp');
        $property->setValue($path);

        $node = $this->sessionManager->getSession()->getNodeByIdentifier($content->getUuid());

        $contentType = $this->contentTypeManager->get($property->getContentTypeName());
        $contentType->write(
            $node,
            new TranslatedProperty($property, $locale, $this->namespaceRegistry->getPrefix('content_localized')),
            $userId,
            $webspaceKey,
            $locale,
            $segmentKey
        );

        $this->sessionManager->getSession()->save();
    }

    private function loadDocument($pathOrUuid, $locale, $options, $shouldExclude = true)
    {
        $document = $this->documentManager->find($pathOrUuid, $locale, [
            'load_ghost_content' => isset($options['load_ghost_content']) ? $options['load_ghost_content'] : true,
        ]);

        if ($shouldExclude && $this->optionsShouldExcludeDocument($document, $options)) {
            return;
        }

        return $document;
    }

    private function optionsShouldExcludeDocument($document, array $options = null)
    {
        if ($options === null) {
            return false;
        }

        $options = array_merge([
            'exclude_ghost' => true,
            'exclude_shadow' => true,
        ], $options);

        $state = $this->inspector->getLocalizationState($document);

        if ($options['exclude_ghost'] && $state == LocalizationState::GHOST) {
            return true;
        }

        if ($options['exclude_ghost'] && $options['exclude_shadow'] && $state == LocalizationState::SHADOW) {
            return true;
        }

        return false;
    }

    /**
     * Initializes cache of extension data.
     */
    private function initializeExtensionCache()
    {
        $this->extensionDataCache = new ArrayCache();
    }

    /**
     * Return a structure bridge corresponding to the given document.
     *
     * @param DocumentInterface $document
     *
     * @return StructureBridge
     */
    private function documentToStructure($document)
    {
        if (null === $document) {
            return;
        }
        $structure = $this->inspector->getStructureMetadata($document);
        $documentAlias = $this->inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return $structureBridge;
    }

    /**
     * Return a collection of structures for the given documents, optionally filtering according
     * to the given options (as defined in optionsShouldExcludeDocument).
     *
     * @param object[] $documents
     * @param array|null $filterOptions
     */
    private function documentsToStructureCollection($documents, $filterOptions = null)
    {
        $collection = [];
        foreach ($documents as $document) {
            if (!$document instanceof StructureBehavior) {
                continue;
            }

            if ($this->optionsShouldExcludeDocument($document, $filterOptions)) {
                continue;
            }

            $collection[] = $this->documentToStructure($document);
        }

        return $collection;
    }
}
