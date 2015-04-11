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

use DateTime;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Jackalope\Query\Row;
use PHPCR\NodeInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use PHPCR\RepositoryException;
use Sulu\Component\Content\BreadcrumbItem;
use Sulu\Component\Content\Type\ContentTypeInterface;
use Sulu\Component\Content\Type\ContentTypeManager;
use Sulu\Component\Content\Mapper\Event\ContentNodeEvent;
use Sulu\Component\Content\Mapper\Event\ContentNodeOrderEvent;
use Sulu\Component\Content\Exception\ExtensionNotFoundException;
use Sulu\Component\Content\Exception\InvalidNavigationContextExtension;
use Sulu\Component\Content\Exception\MandatoryPropertyException;
use Sulu\Component\Content\Exception\StateNotFoundException;
use Sulu\Component\Content\Exception\TranslatedNodeNotFoundException;
use Sulu\Component\Content\Exception\InvalidOrderPositionException;
use Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Document\Property\PropertyInterface;
use Sulu\Component\Content\Section\SectionPropertyInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Structure\Factory\StructureFactoryInterface;
use Sulu\Component\Content\StructureType;
use Sulu\Component\Content\Compat\Template\TemplateResolver;
use Sulu\Component\Content\Template\Exception\TemplateNotFoundException;
use Sulu\Component\Content\Type\Core\ResourceLocatorInterface;
use Sulu\Component\Content\Type\Core\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\NodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Sulu\Component\Util\SuluNodeHelper;
use PHPCR\PropertyType;
use Sulu\Component\Content\Mapper\Event\ContentNodeDeleteEvent;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Compat\DataNormalizer;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Form\FormFactoryInterface;
use Sulu\Component\Content\Form\Exception\InvalidFormException;
use Sulu\Component\Content\Compat\Stucture\LegacyStructureConstants;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;

/**
 * Maps content nodes to phpcr nodes with content types and provides utility function to handle content nodes
 *
 * Short term todo:
 *
 * - Rename localization, locale, language etc. to "locale"
 *
 * @package Sulu\Component\Content\Mapper
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
    private $extensionManager;

    /**
     * @var StructureFactoryInterface
     */
    private $structureFactory;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LocalizationFinderInterface
     */
    private $localizationFinder;

    /**
     * namespace of translation
     * @var string
     */
    private $languageNamespace;

    /**
     * prefix for internal properties
     * @var string
     */
    private $internalPrefix;

    /**
     * default language of translation
     * @var string
     */
    private $defaultLanguage;

    /**
     * default template
     * @var string[]
     */
    private $defaultTemplates;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var PathCleanupInterface
     */
    private $cleaner;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var TemplateResolver
     */
    private $templateResolver;

    /**
     * excepted states
     * @var array
     */
    private $states = array(
        WorkflowStage::PUBLISHED,
        WorkflowStage::TEST
    );

    /**
     * @var MultipleTranslatedProperties
     */
    private $properties;

    /**
     * @var boolean
     */
    private $ignoreMandatoryFlag = false;

    /**
     * @var boolean
     */
    private $noRenamingFlag = false;

    /**
     * @var Cache
     */
    private $extensionDataCache;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * @var DataNormalizer
     */
    private $dataNormalizer;

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

    public function __construct(
        DocumentManager $documentManager,
        StructureFactoryInterface $structureFactory,
        ExtensionManager $extensionManager,
        DataNormalizer $dataNormalizer,
        WebspaceManagerInterface $webspaceManager,
        FormFactoryInterface $formFactory,
        DocumentInspector $documentInspector,

        SessionManagerInterface $sessionManager,
        ContentTypeManager $contentTypeManager,
        EventDispatcherInterface $eventDispatcher,
        LocalizationFinderInterface $localizationFinder,
        PathCleanupInterface $cleaner,
        TemplateResolver $templateResolver,
        SuluNodeHelper $nodeHelper,
        RlpStrategyInterface $strategy,
        $defaultLanguage,
        $defaultTemplates,
        $languageNamespace,
        $internalPrefix,
        $stopwatch = null
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->extensionManager = $extensionManager;
        $this->dataNormalizer = $dataNormalizer;
        $this->structureFactory = $structureFactory;
        $this->sessionManager = $sessionManager;
        $this->webspaceManager = $webspaceManager;
        $this->documentManager = $documentManager;
        $this->formFactory = $formFactory;
        $this->inspector = $documentInspector;

        // deprecated
        $this->localizationFinder = $localizationFinder;
        $this->eventDispatcher = $eventDispatcher;
        $this->defaultLanguage = $defaultLanguage;
        $this->defaultTemplates = $defaultTemplates;
        $this->languageNamespace = $languageNamespace;
        $this->internalPrefix = $internalPrefix;
        $this->cleaner = $cleaner;
        $this->templateResolver = $templateResolver;
        $this->nodeHelper = $nodeHelper;
        $this->strategy = $strategy;

        // optional
        $this->stopwatch = $stopwatch;
    }

    /**
     * Create a new property translator
     *
     * @param string $locale
     * @param string $structureType
     *
     * @return MultipleTranslatedProperties
     */
    protected function createPropertyTranslator($locale, $structureType = LegacyStructureConstants::TYPE_PAGE)
    {
        $properties = new MultipleTranslatedProperties(
            array(
                'changer',
                'changed',
                'created',
                'creator',
                'published',
                'state',
                'title',
                'template',
                'published',
                'nodeType',
                'navContexts',
                'shadow-on',
                'shadow-base',
                'internal_link'
            ),
            $this->languageNamespace,
            $this->internalPrefix
        );

        $properties->setLanguage($locale);
        $properties->setStructureType($structureType);

        return $properties;
    }

    /**
     * {@inheritDoc}
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
        $templateKey,
        $webspaceKey,
        $locale,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null,
        $structureType = LegacyStructureConstants::TYPE_PAGE
    ) {
        // $event = new ContentNodeEvent($node, $structure);
        // $this->eventDispatcher->dispatch(ContentEvents::NODE_PRE_SAVE, $event);

        $data = $this->dataNormalizer->normalize($data, $state, $parentUuid);

        $content = $data['content'];
        unset($data['content']);

        if ($uuid) {
            $document = $this->documentManager->find($uuid, $locale, $structureType);
        } else {
            $document = $this->documentManager->create($structureType);
        }

        $form = $this->formFactory->create($structureType, $document, array(
            'webspace_key' => $webspaceKey,
            'structure_name' => $templateKey,
        ));

        $form->submit($data, false);

        // TODO: Refactor the content so that conetnt types are agnostic to the node types
        //       Currently it is not possible to map content with a form as content types
        //       can do whatever they want in terms of mapping.
        $document->getContent()->bind($content);

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        $this->documentManager->persist($document, $locale);
        $this->documentManager->flush();

        return $document;
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
        $document = $this->documentManager->find($uuid, $locale);

        if ($document === null) {
            throw new TranslatedNodeNotFoundException($uuid, $locale);
        }

        if (!$document instanceof ExtensionBehavior) {
            throw new \RuntimeException(sprintf(
                'Document of class "%s" must implement the ExtensionableBehavior if it is to be extended',
                get_class($document)
            ));
        }

        // check if extension exists
        if (false === $this->extensionManager->hasExtension($document->getStructureType(), $extensionName)) {
            throw new ExtensionNotFoundException($document->getStructureType(), $extensionName);
        }

        // save data of extensions
        $extension = $this->structureFactory->getExtension($document->getStructureType(), $extensionName);
        $node = $this->documentRegistry->getNodeForDocument($document);

        $extension->save($node, $data, $webspaceKey, $locale);
        $extensionData = $extension->load($node, $webspaceKey, $locale);

        $document->setExtension($extension->getName(), $extensionData);

        $this->documentManager->flush();

        return $document;
    }

    /**
     * {@inheritdoc}
     */
    public function saveStartPage(
        $data,
        $templateKey,
        $webspaceKey,
        $locale,
        $userId,
        $partialUpdate = true,
        $isShadow = null,
        $shadowBaseLanguage = null
    ) {
        $uuid = $this->inspector->getUuid($this->getContentDocument($webspaceKey, $locale));

        return $this->save(
            $data,
            $templateKey,
            $webspaceKey,
            $locale,
            $userId,
            $partialUpdate,
            $uuid,
            null,
            WorkflowStage::PUBLISHED,
            $isShadow,
            $shadowBaseLanguage
        );
    }
    
    public function loadByParent(
        $uuid,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $ignoreExceptions = false,
        $excludeGhosts = false
    )
    {
        $parent = null;
        if ($uuid) {
            $parent = $this->documentManagerfind($uuid, $languageCode);
        }

        if (null === $parent) {
            $parent = $this->getContentDocument($webspaceKey, $languageCode);
        }

        $fetchDepth = -1;
        if (false === $flat) {
            $fetchDepth = $depth;
        }

        $children = $this->documentInspector->getChildren($parent, null, $fetchDepth, $languageCode);
        $children = $children->getArrayCopy();

        if ($flat) {
            foreach ($children as $child) {
                if ($depth === null || $depth > 1) {
                    $childChildren = $this->loadByParent(
                        $this->inspector->getUuid($child),
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
        return $this->documentManager->find($uuid, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function loadStartPage($webspaceKey, $locale)
    {
        $startPage = $this->getContentDocument($webspaceKey);
        $startPage->setWorkflowStage(WorkflowStage::PUBLISHED);
        $startPage->setNavgationContexts(array());

        return $startPage;
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

        return $this->loadDocument($uuid, $locale, array(
            'exclude_shadow' => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function loadBySql2($sql2, $locale, $webspaceKey, $limit = null)
    {
        $query = $this->documentManager->createQuery($sql2, $locale);
        $query->setMaxResults($limit);

        return $query->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function loadByQuery(
        QueryInterface $query,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {

        throw new \Exception('Implement locale on query');
        return $this->documentManager->getDocumentsByPhpcrQuery($query, null, null, $locale); // locale not implemented
    }

    /**
     * {@inheritdoc}
     */
    public function loadTreeByUuid(
        $uuid,
        $locale,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $webspaceChildren = $this->documentInspector->getChildren($this->getContentDocument($webspaceKey, $locale));

        return $this->filterDocuments($webspaceChildren, array(
            'load_ghost_content' => $loadGhostContent,
            'exclude_ghost' => $excludeGhost
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function loadTreeByPath(
        $path,
        $locale,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        return $this->loadTreeByUuid(null, $locale, null, $excludeGhost, $loadGhostContent);
    }

    /**
     * Load/hydrate a shalow structure with the given node.
     * Shallow structures do not have content properties / extensions
     * hydrated.
     *
     * @param NodeInterface $node
     * @param string $localization
     * @param string $webspaceKey
     *
     * @return StructureInterface
     */
    public function loadShallowStructureByNode(NodeInterface $contentNode, $localization, $webspaceKey)
    {
        return $this->documentManager->find($contentNode->getPath());
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
        return $this->loadDocument(
            $node->getIdentifier(),
            $locale,
            array(
                'load_ghost_content' => $loadGhostContent,
                'exclude_ghost' => $excludeGhost,
                'exclude_shadow' => $excludeShadow,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadBreadcrumb($uuid, $locale, $webspaceKey)
    {
        throw new \InvalidArgumentException(
            'Implement getDepth in DocumentInspector' . 
            'Refactor this'
        );

        $document = $this->documentManager->find($uuid, $locale);

        $documents = array();
        $webspaceDocument = $this->getContentDocument($webspaceKey, $locale);

        do {
            $documents[] = $document;

            // TODO: Use document inspector to get parent
            $document = $document->getParent();
        } while ($document instanceof DocumentInterface && $document->getDepth() >= $contentDocument->getDepth());

        $items = array();
        foreach ($documents as $document) {
            $items[] = new BreadcrumbItem(
                $this->inspector->getDepth(document) - $this->inspector->getDepth($contentDocument),
                $this->inspector->getUuid($document->getUuid()),
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
        throw new \RuntimeException(
            'Implement recursive deleting'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function move($uuid, $destParentUuid, $userId, $webspaceKey, $locale)
    {
        $document = $this->documentManager->find($uuid);
        $this->documentManager->move($document, $destParentUuid);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($uuid, $destParentUuid, $userId, $webspaceKey, $locale)
    {
        $document = $this->documentManager->find($uuid);
        $this->documentManager->copy($document, $destParentUuid);
    }

    /**
     * {@inheritDoc}
     */
    public function copyLanguage(
        $uuid,
        $userId,
        $webspaceKey,
        $srcLanguageCode,
        $destLanguageCodes,
        $structureType = LegacyStructureConstants::TYPE_PAGE
    ) {
        throw new \RuntimeException('Do this');
        if (!is_array($destLanguageCodes)) {
            $destLanguageCodes = array($destLanguageCodes);
        }

        $document = $this->documentManager->find($uuid, $srcLanguageCode);

        $parentNode = $this->getSession()->getNodeByIdentifier($document->getUuid())->getParent();
        $resourceLocator = $this->getResourceLocator();

        $data = $structure->toArray(true);
        foreach ($destLanguageCodes as $destLanguageCode) {
            if ($structure->hasTag('sulu.rlp')) {
                $parentUrl = $resourceLocator->getResourceLocatorByUuid(
                    $parentNode->getIdentifier(),
                    $webspaceKey,
                    $destLanguageCode
                );
                $rlp = $this->getResourceLocator()->getStrategy()->generate(
                    $document->getTitle(),
                    $parentUrl,
                    $webspaceKey,
                    $destLanguageCode
                );

                $data[$structure->getPropertyByTagName('sulu.rlp')->getName()] = $rlp;
            }

            $this->save(
                $data,
                $structure->getKey(),
                $webspaceKey,
                $destLanguageCode,
                $userId,
                false,
                $uuid,
                null,
                Structure::STATE_TEST,
                $structure->getIsShadow(),
                $structure->getShadowBaseLanguage(),
                $structureType
            );
        }

        return $structure;
    }

    /**
     * {@inheritDoc}
     */
    public function orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $locale)
    {
        throw new \RuntimeException('Do this');
    }

    /**
     * {@inheritDoc}
     */
    public function orderAt($uuid, $position, $userId, $webspaceKey, $locale)
    {
        throw new \RuntimeException('Do this');
    }

    /**
     * TODO: Refactor this. This should not effect the global state of the object, this
     *       should be scoped for each save request.
     *
     * TRUE dont rename pages on save
     * @param boolean $noRenamingFlag
     * @return $this
     */
    public function setNoRenamingFlag($noRenamingFlag)
    {
        $this->noRenamingFlag = $noRenamingFlag;

        return $this;
    }

    /**
     * TRUE ignores mandatory in save
     * @param bool $ignoreMandatoryFlag
     * @return $this
     */
    public function setIgnoreMandatoryFlag($ignoreMandatoryFlag)
    {
        $this->ignoreMandatoryFlag = $ignoreMandatoryFlag;

        return $this;
    }

    /**
     * @param string $webspaceKey
     * @param string $locale
     * @param bool $move
     * @return StructureInterface
     */
    private function copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $locale, $move = true)
    {
        throw new \Exception('Do this');
        $propertyTranslator = $this->createPropertyTranslator($locale);
        // find localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $localizations = $webspace->getAllLocalizations();

        // prepare utility
        $session = $this->getSession();

        // load from phpcr
        $node = $session->getNodeByIdentifier($uuid);
        $parentNode = $session->getNodeByIdentifier($destParentUuid);

        // prepare content node
        $content = $this->loadByNode($node, $locale, $webspaceKey, false, true);
        $nodeName = $content->getPropertyValue('title');

        // node name should not have a slash
        $nodeName = str_replace('/', '-', $nodeName);

        $nodeName = $this->cleaner->cleanup($nodeName, $locale);
        $nodeName = $this->getUniquePath($nodeName, $parentNode);

        // prepare pathes
        $path = $node->getPath();
        $destPath = $parentNode->getPath() . '/' . $nodeName;

        if ($move) {
            // move node
            $session->move($path, $destPath);
        } else {
            // copy node
            $session->getWorkspace()->copy($path, $destPath);
            $session->save();

            // load new phpcr and content node
            $node = $session->getNode($destPath);
        }

        foreach ($localizations as $locale) {
            $content = $this->loadByNode($node, $locale->getLocalization(), $webspaceKey, false, true);

            // prepare parent content node
            $parentContent = $this->loadByNode($parentNode, $locale->getLocalization(), $webspaceKey, false, true);
            $parentResourceLocator = '/';
            if ($parentContent->hasTag('sulu.rlp')) {
                $parentResourceLocator = $parentContent->getPropertyValueByTagName('sulu.rlp');
            }
            // correct resource locator
            if (
                $content->getType() === null && $content->hasTag('sulu.rlp') &&
                $content->getNodeType() === RedirectType::NONE
            ) {
                $this->adaptResourceLocator(
                    $content,
                    $node,
                    $parentResourceLocator,
                    $webspaceKey,
                    $locale->getLocalization(),
                    $userId
                );

                // set changer of node
                $propertyTranslator->setLanguage($locale);
                $node->setProperty($propertyTranslator->getName('changer'), $userId);
                $node->setProperty($propertyTranslator->getName('changed'), new DateTime());
            }
        }

        // set changer of node in specific language
        $this->setChanger($node, $userId, $locale);

        $session->save();

        return $this->loadByNode($node, $locale, $webspaceKey);
    }

    private function setChanger(NodeInterface $node, $userId, $locale)
    {
        $propertyTranslator = $this->createPropertyTranslator($locale);
        $node->setProperty($propertyTranslator->getName('changer'), $userId);
        $node->setProperty($propertyTranslator->getName('changed'), new DateTime());
    }

    /**
     * adopts resource locator for just moved or copied node
     * @param StructureInterface $content
     * @param NodeInterface $node
     * @param string $parentResourceLocator
     * @param string $webspaceKey
     * @param string $locale
     * @param int $userId
     */
    private function adaptResourceLocator(
        StructureInterface $content,
        NodeInterface $node,
        $parentResourceLocator,
        $webspaceKey,
        $locale,
        $userId
    ) {
        // prepare objects
        $property = $content->getPropertyByTagName('sulu.rlp');
        $translatedProperty = new TranslatedProperty($property, $locale, $this->languageNamespace);
        $contentType = $this->getResourceLocator();
        $strategy = $contentType->getStrategy();

        // get resource locator pathes
        $srcResourceLocator = $content->getPropertyValueByTagName('sulu.rlp');

        if ($srcResourceLocator !== null) {
            $resourceLocatorPart = PathHelper::getNodeName($srcResourceLocator);
        } else {
            $resourceLocatorPart = $content->getPropertyValue('title');
        }

        // generate new resourcelocator
        $destResourceLocator = $strategy->generate(
            $resourceLocatorPart,
            $parentResourceLocator,
            $webspaceKey,
            $locale
        );

        // save new resource-locator
        $property->setValue($destResourceLocator);
        $contentType->write($node, $translatedProperty, $userId, $webspaceKey, $locale, null);
    }

    /**
     * Remove node with references (path, history path ...)
     *
     * @param NodeInterface $node
     * @param string Webspace - required by event listeners
     * @param boolean $dereference Remove REFERENCE properties (or property
     *   values in the case of multi-value) from referencing nodes
     */
    private function deleteRecursively(NodeInterface $node, $webspace, $dereference = false)
    {
        foreach ($node->getReferences() as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                $child = $ref->getParent();

                if ($dereference) {
                    if ($ref->isMultiple()) {
                        $values = $ref->getValue();
                        foreach ($values as $i => $referringNode) {
                            if ($node->getIdentifier() === $referringNode->getIdentifier()) {
                                unset($values[$i]);
                            }
                        }

                        $ref->getParent()->setProperty($ref->getName(), $values, PropertyType::REFERENCE);
                    } else {
                        $ref->remove();
                    }
                }
            } else {
                $child = $ref;
            }

            if ($this->nodeHelper->hasSuluNodeType($child, array('sulu:path'))) {
                $this->deleteRecursively($child, $webspace, $dereference);
            }
        }

        $dispatchPost = false;

        // if the node being deleted is a structure, dispatch an event
        if ($this->nodeHelper->getStructureTypeForNode($node)) {
            $event = new ContentNodeDeleteEvent($this, $this->nodeHelper, $node, $webspace);
            $this->eventDispatcher->dispatch(ContentEvents::NODE_PRE_DELETE, $event);
            $dispatchPost = true;
        }

        $node->remove();

        if (true === $dispatchPost) {
            $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_DELETE, $event);
        }
    }

    /**
     * @return ResourceLocatorInterface
     */
    public function getResourceLocator()
    {
        return $this->getContentType('resource_locator');
    }

    /**
     * returns a type with given name
     * @param $name
     * @return ContentTypeInterface
     */
    protected function getContentType($name)
    {
        return $this->contentTypeManager->get($name);
    }

    /**
     * @param $webspaceKey
     * @return Document
     */
    private function getContentDocument($webspaceKey, $locale)
    {
        return $this->documentManager->find(
            $this->sessionManager->getContentPath($webspaceKey),
            $locale
        );
    }

    /**
     * @param $webspaceKey
     * @param string $locale
     * @param string $segment
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
        throw new \Exception('Do This');
    }

    /**
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
            new TranslatedProperty($property, $locale, $this->languageNamespace),
            $userId,
            $webspaceKey,
            $locale,
            $segmentKey
        );

        $this->sessionManager->getSession()->save();
    }

    private function validateRequired(ContentMapperRequest $request, $keys)
    {
        foreach ($keys as $required) {
            $method = 'get' . ucfirst($required);
            $val = $request->$method();

            if (null === $val) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'ContentMapperRequest "%s" cannot be null',
                        $required
                    )
                );
            }
        }
    }

    private function loadDocument($pathOrUuid, $locale, $options)
    {
        $options = array_merge(array(
            'load_ghost_content' => false,
            'exclude_ghost' => true,
            'exclude_shadow' => true,
        ), $options);

        $document = $this->documentManager->find($pathOrUuid, $locale);

        if ($this->optionsShouldExcludeDocument($document, $options)) {
            return null;
        }

        return $document;
    }

    private function filterDocuments($documents, $locale, $options)
    {
        $options = array_merge(array(
            'load_ghost_content' => false,
            'exclude_ghost' => true,
            'exclude_shadow' => true,
        ), $options);

        $collection = array();
        foreach ($documents as $document) {
            if ($this->optionsShouldExcludeDocument($document, $options)) {
                continue;
            }

            $collection[] = $this->loadDocument($document, $locale, $options);
        }

        return $collection;
    }

    private function optionsShouldExcludeDocument($document, array $options = array())
    {
        $state = $this->inspector->getLocalizationState($document);

        $isShadowOrGhost = $state === LocalizationState::GHOST || $state === LocalizationState::SHADOW;

        if (($options['exclude_ghost'] && $options['exclude_shadow']) && $isShadowOrGhost) {
            return true;
        }

        return false;
    }
}
