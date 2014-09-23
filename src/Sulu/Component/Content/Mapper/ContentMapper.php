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
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use Sulu\Component\Content\BreadcrumbItem;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentEvents;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Sulu\Component\Content\Exception\ExtensionNotFoundException;
use Sulu\Component\Content\Exception\InvalidNavigationContextExtension;
use Sulu\Component\Content\Exception\MandatoryPropertyException;
use Sulu\Component\Content\Exception\StateNotFoundException;
use Sulu\Component\Content\Exception\TranslatedNodeNotFoundException;
use Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Section\SectionPropertyInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureExtension\StructureExtension;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\StructureType;
use Sulu\Component\Content\Template\TemplateResolverInterface;
use Sulu\Component\Content\Template\Exception\TemplateNotFoundException;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\ArrayableInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Maps content nodes to phpcr nodes with content types and provides utility function to handle content nodes
 * @package Sulu\Component\Content\Mapper
 */
class ContentMapper implements ContentMapperInterface
{
    /**
     * @var ContentTypeManager
     */
    private $contentTypeManager;

    /**
     * @var StructureManagerInterface
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
     * @var string
     */
    private $defaultTemplate;

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
     * @var TemplateResolverInterface
     */
    private $templateResolver;

    /**
     * excepted states
     * @var array
     */
    private $states = array(
        StructureInterface::STATE_PUBLISHED,
        StructureInterface::STATE_TEST
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

    public function __construct(
        ContentTypeManager $contentTypeManager,
        StructureManagerInterface $structureManager,
        SessionManagerInterface $sessionManager,
        EventDispatcherInterface $eventDispatcher,
        LocalizationFinderInterface $localizationFinder,
        PathCleanupInterface $cleaner,
        WebspaceManagerInterface $webspaceManager,
        TemplateResolverInterface $templateResolver,
        $defaultLanguage,
        $defaultTemplate,
        $languageNamespace,
        $internalPrefix,
        $stopwatch = null
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->sessionManager = $sessionManager;
        $this->localizationFinder = $localizationFinder;
        $this->eventDispatcher = $eventDispatcher;
        $this->defaultLanguage = $defaultLanguage;
        $this->defaultTemplate = $defaultTemplate;
        $this->languageNamespace = $languageNamespace;
        $this->internalPrefix = $internalPrefix;
        $this->cleaner = $cleaner;
        $this->webspaceManager = $webspaceManager;
        $this->templateResolver = $templateResolver;

        // optional
        $this->stopwatch = $stopwatch;

        // properties
        $this->properties = new MultipleTranslatedProperties(
            array(
                'changer',
                'changed',
                'created',
                'creator',
                'state',
                'template',
                'published',
                'nodeType',
                'navContexts',
                'shadow-on',
                'shadow-base'
            ),
            $this->languageNamespace,
            $this->internalPrefix
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        $data,
        $templateKey,
        $webspaceKey,
        $languageCode,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null
    ) {
        // create translated properties
        $this->properties->setLanguage($languageCode);

        // set default node-type
        if (!isset($data['nodeType'])) {
            $data['nodeType'] = Structure::NODE_TYPE_CONTENT;
        }

        $resolvedTemplateKey = $this->templateResolver->resolve($data['nodeType'], $templateKey);
        $structure = $this->getStructure($resolvedTemplateKey);

        $session = $this->getSession();

        if ($parentUuid !== null) {
            $root = $session->getNodeByIdentifier($parentUuid);
        } else {
            $root = $this->getContentNode($webspaceKey);
        }

        $nodeNameProperty = $structure->getPropertyByTagName('sulu.node.name');
        $translatedNodeNameProperty = new TranslatedProperty(
            $nodeNameProperty,
            $languageCode,
            $this->languageNamespace
        );

        $dateTime = new \DateTime();

        $newTranslatedNode = function (NodeInterface $node) use ($userId, $dateTime, &$state) {
            $node->setProperty($this->properties->getName('creator'), $userId);
            $node->setProperty($this->properties->getName('created'), $dateTime);

            if (!isset($state)) {
                $state = StructureInterface::STATE_TEST;
            }
        };

        /** @var NodeInterface $node */
        if ($uuid === null) {
            // create a new node
            $path = $this->cleaner->cleanUp($data[$nodeNameProperty->getName()], $languageCode);
            $path = $this->getUniquePath($path, $root);
            $node = $root->addNode($path);
            $newTranslatedNode($node);

            $node->addMixin('sulu:content');
        } else {
            $node = $session->getNodeByIdentifier($uuid);

            if (!$node->hasProperty($this->properties->getName('template'))) {
                $newTranslatedNode($node);
            } else {
                $hasSameLanguage = ($languageCode == $this->defaultLanguage);
                $hasSamePath = ($node->getPath() !== $this->getContentNode($webspaceKey)->getPath());
                $hasDifferentTitle = !$node->hasProperty($translatedNodeNameProperty->getName()) ||
                    $node->getPropertyValue(
                        $translatedNodeNameProperty->getName()
                    ) !== $data[$nodeNameProperty->getName()];

                if (!$this->noRenamingFlag && $hasSameLanguage && $hasSamePath && $hasDifferentTitle) {
                    $path = $this->cleaner->cleanUp($data[$nodeNameProperty->getName()], $languageCode);
                    $path = $this->getUniquePath($path, $node->getParent());

                    if ($path) {
                        $node->rename($path);
                    }
                    // FIXME refresh session here
                }
            }
        }

        if ($isShadow) {
            $this->validateShadow($node, $languageCode, $shadowBaseLanguage);

            // If the URL for the shadow resource locator is not set, fallback to the shadow page for the
            // shadow base resource locator
            if ($structure->hasTag('sulu.rlp')) {
                $property = $structure->getPropertyByTagName('sulu.rlp');
                $baseLanguageRlProperty = new TranslatedProperty(
                    $property,
                    $shadowBaseLanguage,
                    $this->languageNamespace
                );
                if (!isset($data[$property->getName()])) {
                    $rlpContentType = $this->getContentType($baseLanguageRlProperty->getContentTypeName());
                    $rlpContentType->read($node, $baseLanguageRlProperty, $webspaceKey, $shadowBaseLanguage, null);
                    $rl = $baseLanguageRlProperty->getValue();
                    $data[$property->getName()] = $rl;
                }
            }
        }

        $shadowChanged = false;

        if ($node->hasProperty($this->properties->getName('shadow-on'))) {
            $oldShadowStatus = $node->getPropertyValue($this->properties->getName('shadow-on'));
            if ($isShadow !== $oldShadowStatus) {
                $shadowChanged = true;
            }
        }

        $node->setProperty($this->properties->getName('changer'), $userId);
        $node->setProperty($this->properties->getName('changed'), $dateTime);
        $node->setProperty($this->properties->getName('template'), $templateKey);
        $node->setProperty($this->properties->getName('shadow-on'), $isShadow);
        $node->setProperty($this->properties->getName('shadow-base'), $shadowBaseLanguage);

        if (isset($data['nodeType'])) {
            $node->setProperty($this->properties->getName('nodeType'), $data['nodeType']);
        }

        // do not state transition for root (contents) node
        $contentRootNode = $this->getContentNode($webspaceKey);
        if ($node->getPath() !== $contentRootNode->getPath() && isset($state)) {
            $this->changeState(
                $node,
                $state,
                $structure,
                $this->properties->getName('state'),
                $this->properties->getName('published')
            );
        }

        if (isset($data['navContexts']) && $data['navContexts'] !== false
            && $this->validateNavContexts($data['navContexts'], $this->webspaceManager->findWebspaceByKey($webspaceKey))
        ) {
            $node->setProperty($this->properties->getName('navContexts'), $data['navContexts']);
        }

        // if the shadow status has changed, do not process the rest of the form.
        $postSave = array();
        if (false === $shadowChanged) {

            // go through every property in the template
            /** @var PropertyInterface $property */
            foreach ($structure->getProperties(true) as $property) {
                // allow null values in data
                if (isset($data[$property->getName()])) {
                    $type = $this->getContentType($property->getContentTypeName());
                    $value = $data[$property->getName()];
                    $property->setValue($value);

                    // add property to post save action
                    if ($type->getType() == ContentTypeInterface::POST_SAVE) {
                        $postSave[] = array(
                            'type' => $type,
                            'property' => $property
                        );
                    } else {
                        $type->write(
                            $node,
                            new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                            $userId,
                            $webspaceKey,
                            $languageCode,
                            null
                        );
                    }
                } elseif ($isShadow) {
                    // nothing
                } elseif (!$this->ignoreMandatoryFlag && $property->getMandatory()) {
                    $type = $this->getContentType($property->getContentTypeName());
                    $translatedProperty = new TranslatedProperty($property, $languageCode, $this->languageNamespace);

                    if (false === $type->hasValue($node, $translatedProperty, $webspaceKey, $languageCode, null)) {
                        throw new MandatoryPropertyException($templateKey, $property);
                    }
                } elseif (!$partialUpdate) {
                    $type = $this->getContentType($property->getContentTypeName());
                    // if it is not a partial update remove property
                    $type->remove(
                        $node,
                        new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                        $webspaceKey,
                        $languageCode,
                        null
                    );
                }
                // if it is a partial update ignore property
            }
        }

        // save node now
        $session->save();

        // set post save content types properties
        foreach ($postSave as $post) {
            try {
                /** @var ContentTypeInterface $type */
                $type = $post['type'];
                /** @var PropertyInterface $property */
                $property = $post['property'];

                $type->write(
                    $node,
                    new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                    $userId,
                    $webspaceKey,
                    $languageCode,
                    null
                );
            } catch (\Exception $ex) {
                // TODO Introduce a PostSaveException, so that we don't have to catch everything
                // FIXME message for user or log entry
                throw $ex;
            }
        }
        $session->save();

        if (false === $shadowChanged) {
            // save data of extensions
            $ext = array();
            foreach ($this->structureManager->getExtensions($structure->getKey()) as $extension) {
                $extension->setLanguageCode($languageCode, $this->languageNamespace, $this->internalPrefix);
                if (isset($data['ext']) && isset($data['ext'][$extension->getName()])) {
                    $extension->save(
                        $node,
                        $data['ext'][$extension->getName()],
                        $webspaceKey,
                        $languageCode
                    );
                }
                $ext[$extension->getName()] = $extension->load($node, $webspaceKey, $languageCode);
            }

            $structure->setExt($ext);
        }

        $session->save();

        $structure->setUuid($node->getPropertyValue('jcr:uuid'));
        $structure->setPath(str_replace($this->getContentNode($webspaceKey)->getPath(), '', $node->getPath()));
        $structure->setNodeType(
            $node->getPropertyValueWithDefault($this->properties->getName('nodeType'), Structure::NODE_TYPE_CONTENT)
        );
        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($languageCode);
        $structure->setCreator($node->getPropertyValue($this->properties->getName('creator')));
        $structure->setChanger($node->getPropertyValue($this->properties->getName('changer')));
        $structure->setCreated($node->getPropertyValue($this->properties->getName('created')));
        $structure->setChanged($node->getPropertyValue($this->properties->getName('changed')));
        $structure->setIsShadow($node->getPropertyValueWithDefault($this->properties->getNAme('shadow-on'), false));
        $structure->setShadowBaseLanguage(
            $node->getPropertyValueWithDefault($this->properties->getName('shadow-base'), null)
        );
        $structure->setEnabledShadowLanguages(
            $this->getEnabledShadowLanguages($node)
        );
        $structure->setConcreteLanguages($this->properties->getLanguagesForNode($node));

        $structure->setNavContexts(
            $node->getPropertyValueWithDefault($this->properties->getName('navContexts'), array())
        );
        $structure->setPublished(
            $node->getPropertyValueWithDefault($this->properties->getName('published'), null)
        );
        $structure->setOriginTemplate(
            $node->getPropertyValueWithDefault($this->properties->getName('template'), $this->defaultLanguage)
        );

        // load dependencies for internal links
        $this->loadInternalLinkDependencies(
            $structure,
            $languageCode,
            $webspaceKey
        );

        // throw an content.node.save event
        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_SAVE, $event);

        return $structure;
    }

    /**
     * Validate a shadow language mapping.
     *
     * If a node of $language is set to shadow language $shadowBaseLanguage then
     * it must not shadow either itself or a language which is not concrete.
     *
     * @param NodeInterface $node
     * @param string $language
     * @param string $shadowBaseLanguage
     * @throws \RuntimeException
     */
    protected function validateShadow(NodeInterface $node, $language, $shadowBaseLanguage)
    {
        if ($language == $shadowBaseLanguage) {
            throw new \RuntimeException(
                sprintf(
                    'Attempting to make language "%s" a shadow of itself! ("%s")',
                    $language,
                    $shadowBaseLanguage
                )
            );
        }

        $concreteLanguages = $this->getConcreteLanguages($node);

        if (!in_array($shadowBaseLanguage, $concreteLanguages)) {
            throw new \RuntimeException(
                sprintf(
                    'Attempting to make language "%s" a shadow of a non-concrete language "%s". Concrete languages are "%s"',
                    $language,
                    $shadowBaseLanguage,
                    implode(', ', $concreteLanguages)
                )
            );
        }

    }

    /**
     * Return the enabled shadow languages on the given node
     *
     * @param NodeInterface $node
     *
     * @return array
     */
    protected function getEnabledShadowLanguages(NodeInterface $node)
    {
        $nodeLanguages = $this->properties->getLanguagesForNode($node);
        $shadowBaseLanguages = array();

        foreach ($nodeLanguages as $nodeLanguage) {
            $propertyMap = clone $this->properties;
            $propertyMap->setLanguage($nodeLanguage);

            $shadowOn = $node->getPropertyValueWithDefault($propertyMap->getName('shadow-on'), null);

            if ($shadowOn) {
                $nodeShadowBaseLanguage = $node->getPropertyValueWithDefault(
                    $propertyMap->getName('shadow-base'),
                    null
                );

                if (null !== $nodeShadowBaseLanguage) {
                    $shadowBaseLanguages[$nodeShadowBaseLanguage] = $nodeLanguage;
                }
            }
        }

        return $shadowBaseLanguages;
    }

    /**
     * Return the "concrete" languages in a node - i.e. all languages
     * excluding shadow languages.
     *
     * @param NodeInterface $node
     *
     * @return array
     */
    protected function getConcreteLanguages(NodeInterface $node)
    {
        $enabledLanguages = $this->properties->getLanguagesForNode($node);
        $enabledShadowLanguages = $this->getEnabledShadowLanguages($node);
        $concreteTranslations = array_diff($enabledLanguages, array_values($enabledShadowLanguages));

        return $concreteTranslations;
    }

    /**
     * validates navigation contexts
     * @param string[] $navContexts
     * @param \Sulu\Component\Webspace\Webspace $webspace
     * @throws \Sulu\Component\Content\Exception\InvalidNavigationContextExtension
     * @return boolean
     */
    private function validateNavContexts($navContexts, Webspace $webspace)
    {
        $webspaceContextKeys = $webspace->getNavigation()->getContextKeys();
        foreach ($navContexts as $context) {
            if (!in_array($context, $webspaceContextKeys)) {
                throw new InvalidNavigationContextExtension($navContexts, $webspaceContextKeys);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function saveExtension(
        $uuid,
        $data,
        $extensionName,
        $webspaceKey,
        $languageCode,
        $userId
    ) {
        // create translated properties
        $this->properties->setLanguage($languageCode);

        // get node from session
        $session = $this->getSession();
        $node = $session->getNodeByIdentifier($uuid);

        // load rest of node
        $structure = $this->loadByNode($node, $languageCode, $webspaceKey, true, true);

        if ($structure === null) {
            throw new TranslatedNodeNotFoundException($uuid, $languageCode);
        }

        // check if extension exists
        if (false === $this->structureManager->hasExtension($structure->getKey(), $extensionName)) {
            throw new ExtensionNotFoundException($structure, $extensionName);
        }

        // set changer / changed
        $dateTime = new \DateTime();
        $node->setProperty($this->properties->getName('changer'), $userId);
        $node->setProperty($this->properties->getName('changed'), $dateTime);

        // save data of extensions
        $extension = $this->structureManager->getExtension($structure->getKey(), $extensionName);
        $extension->save($node, $data, $webspaceKey, $languageCode);
        $ext[$extension->getName()] = $extension->load($node, $webspaceKey, $languageCode);

        $ext = array_merge($structure->getExt(), $ext);
        $structure->setExt($ext);

        $session->save();

        // throw an content.node.save event
        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_SAVE, $event);

        return $structure;
    }

    /**
     * {@inheritdoc}
     */
    private function changeState(
        NodeInterface $node,
        $state,
        StructureInterface $structure,
        $statePropertyName,
        $publishedPropertyName
    ) {
        if (!in_array($state, $this->states)) {
            throw new StateNotFoundException($state);
        }

        // no state (new node) set state
        if (!$node->hasProperty($statePropertyName)) {
            $node->setProperty($statePropertyName, $state);
            $structure->setNodeState($state);

            // published => set only once
            if ($state === StructureInterface::STATE_PUBLISHED && !$node->hasProperty($publishedPropertyName)) {
                $node->setProperty($publishedPropertyName, new DateTime());
            }
        } else {
            $oldState = $node->getPropertyValue($statePropertyName);

            if ($oldState === $state) {
                // do nothing
                return;
            } elseif (
                // from test to published
                $oldState === StructureInterface::STATE_TEST &&
                $state === StructureInterface::STATE_PUBLISHED
            ) {
                $node->setProperty($statePropertyName, $state);
                $structure->setNodeState($state);

                // set only once
                if (!$node->hasProperty($publishedPropertyName)) {
                    $node->setProperty($publishedPropertyName, new DateTime());
                }
            } elseif (
                // from published to test
                $oldState === StructureInterface::STATE_PUBLISHED &&
                $state === StructureInterface::STATE_TEST
            ) {
                $node->setProperty($statePropertyName, $state);
                $structure->setNodeState($state);

                // set published date to null
                $node->getProperty($publishedPropertyName)->remove();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveStartPage(
        $data,
        $templateKey,
        $webspaceKey,
        $languageCode,
        $userId,
        $partialUpdate = true
    ) {
        $uuid = $this->getContentNode($webspaceKey)->getIdentifier();

        return $this->save(
            $data,
            $templateKey,
            $webspaceKey,
            $languageCode,
            $userId,
            $partialUpdate,
            $uuid,
            null,
            StructureInterface::STATE_PUBLISHED
        );
    }

    /**
     * {@inheritDoc}
     */
    public function loadByParent(
        $uuid,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $ignoreExceptions = false,
        $excludeGhosts = false
    ) {
        if ($uuid != null) {
            $root = $this->getSession()->getNodeByIdentifier($uuid);
            // set depth hint specific
            $root = $this->getSession()->getNode($root->getPath(), $depth + 1);
        } else {
            $root = $this->getContentNode($webspaceKey);
        }

        return $this->loadByParentNode(
            $root,
            $webspaceKey,
            $languageCode,
            $depth,
            $flat,
            $ignoreExceptions,
            $excludeGhosts
        );
    }

    /**
     * {@inheritdoc}
     */
    private function loadByParentNode(
        NodeInterface $parent,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $ignoreExceptions = false,
        $excludeGhosts
    ) {
        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.loadByParentNode');
        }

        $results = array();
        $nodes = $parent->getNodes();

        if ($this->stopwatch) {
            $this->stopwatch->lap('contentManager.loadByParentNode');
        }

        /** @var NodeInterface $node */
        foreach ($nodes as $node) {
            try {
                $result = $this->loadByNode($node, $languageCode, $webspaceKey, $excludeGhosts, true);

                if ($result) {
                    $results[] = $result;
                }

                if ($depth === null || $depth > 1) {
                    $children = $this->loadByParentNode(
                        $node,
                        $webspaceKey,
                        $languageCode,
                        $depth !== null ? $depth - 1 : null,
                        $flat,
                        $ignoreExceptions,
                        $excludeGhosts
                    );
                    if ($flat) {
                        $results = array_merge($results, $children);
                    } elseif ($result !== null) {
                        $result->setChildren($children);
                    }
                }
            } catch (TemplateNotFoundException $ex) {
                // ignore pages without valid template
            } catch (\Exception $ex) {
                if (!$ignoreExceptions) {
                    throw $ex;
                }
            }
        }

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadByParentNode');
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function load($uuid, $webspaceKey, $languageCode, $loadGhostContent = false)
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.load');
        }
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        $result = $this->loadByNode($contentNode, $languageCode, $webspaceKey, false, $loadGhostContent, false);

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.load');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function loadStartPage($webspaceKey, $languageCode)
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.loadStartPage');
        }

        $uuid = $this->getContentNode($webspaceKey)->getIdentifier();

        $startPage = $this->load($uuid, $webspaceKey, $languageCode);
        $startPage->setNodeState(StructureInterface::STATE_PUBLISHED);
        $startPage->setNavContexts(array());

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadStartPage');
        }

        return $startPage;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $session = $this->getSession();
        $uuid = $this->getResourceLocator()->loadContentNodeUuid(
            $resourceLocator,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByNode($contentNode, $languageCode, $webspaceKey, true, false, false);
    }

    /**
     * {@inheritdoc}
     */
    public function loadBySql2($sql2, $languageCode, $webspaceKey, $limit = null)
    {
        $structures = array();

        $query = $this->createSql2Query($sql2, $limit);
        $result = $query->execute();

        foreach ($result as $row) {
            try {
                $structure = $this->loadByNode($row->getNode(), $languageCode, $webspaceKey);
                if (null !== $structure) {
                    $structures[] = $structure;
                }
            } catch (TemplateNotFoundException $ex) {
                // ignore pages without valid template
            }
        }

        return $structures;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTreeByUuid(
        $uuid,
        $languageCode,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $node = $this->getSession()->getNodeByIdentifier($uuid);

        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.loadTreeByUuid');
        }

        list($result) = $this->loadTreeByNode($node, $languageCode, $webspaceKey, $excludeGhost, $loadGhostContent);

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadTreeByUuid');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTreeByPath(
        $path,
        $languageCode,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $path = ltrim($path, '/');
        if ($path === '') {
            $node = $this->getContentNode($webspaceKey);
        } else {
            $node = $this->getContentNode($webspaceKey)->getNode($path);
        }

        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.loadTreeByPath');
        }

        list($result) = $this->loadTreeByNode($node, $languageCode, $webspaceKey, $excludeGhost, $loadGhostContent);

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadTreeByPath');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    private function loadTreeByNode(
        NodeInterface $node,
        $languageCode,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false,
        NodeInterface $childNode = null
    ) {
        // go up to content node
        if ($node->getDepth() > $this->getContentNode($webspaceKey)->getDepth()) {
            list($globalResult, $nodeStructure) = $this->loadTreeByNode(
                $node->getParent(),
                $languageCode,
                $webspaceKey,
                $excludeGhost,
                $loadGhostContent,
                $node
            );
        }

        // load children of node
        $result = array();
        $childStructure = null;
        foreach ($node as $child) {
            try {
                $structure = $this->loadByNode($child, $languageCode, $webspaceKey, $excludeGhost, $loadGhostContent);
                if ($structure === null) {
                    continue;
                }

                $result[] = $structure;
                // search structure for child node
                if ($childNode !== null && $childNode === $child) {
                    $childStructure = $structure;
                }
            } catch (TemplateNotFoundException $ex) {
                // ignore pages without valid template
            }
        }

        // set global result once
        if (!isset($globalResult)) {
            $globalResult = $result;
        }
        // set children of structure
        if (isset($nodeStructure)) {
            $nodeStructure->setChildren($result);
        }

        return array($globalResult, $childStructure);
    }

    /**
     * returns a sql2 query
     */
    private function createSql2Query($sql2, $limit = null)
    {
        $queryManager = $this->getSession()->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        if ($limit) {
            $query->setLimit($limit);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    private function loadByNode(
        NodeInterface $contentNode,
        $localization,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false,
        $excludeShadow = true
    ) {
        // first set the language to the given language
        $this->properties->setLanguage($localization);

        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.loadByNode');
            $this->stopwatch->start('contentManager.loadByNode.available-localization');
        }

        if ($loadGhostContent) {
            $availableLocalization = $this->localizationFinder->getAvailableLocalization(
                $contentNode,
                $localization,
                $webspaceKey
            );
        } else {
            $availableLocalization = $localization;
        }

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadByNode.available-localization');
            $this->stopwatch->start('contentManager.loadByNode.mapping');
        }

        $shadowOn = $contentNode->getPropertyValueWithDefault($this->properties->getName('shadow-on'), false);
        $shadowBaseLanguage = $contentNode->getPropertyValueWithDefault(
            $this->properties->getName('shadow-base'),
            false
        );

        $availableLocalization = $this->getShadowLocale($contentNode, $availableLocalization);

        if (($excludeGhost && $excludeShadow) && $availableLocalization != $localization) {
            return null;
        }

        // now switch the language to the available localization
        if ($availableLocalization != $localization) {
            $this->properties->setLanguage($availableLocalization);
        }

        $nodeType = $contentNode->getPropertyValueWithDefault(
            $this->properties->getName('nodeType'),
            Structure::NODE_TYPE_CONTENT
        );

        $templateKey = $contentNode->getPropertyValueWithDefault(
            $this->properties->getName('template'),
            $this->defaultTemplate
        );
        $templateKey = $this->templateResolver->resolve($nodeType, $templateKey);

        $structure = $this->getStructure($templateKey);

        // set structure to ghost, if the available localization does not match the requested one
        if ($availableLocalization != $localization) {
            if ($shadowBaseLanguage) {
                $structure->setType(StructureType::getShadow($availableLocalization));
            } else {
                $structure->setType(StructureType::getGhost($availableLocalization));
            }
        }

        $structure->setHasTranslation($contentNode->hasProperty($this->properties->getName('template')));

        $structure->setIsShadow($shadowOn);
        $structure->setShadowBaseLanguage($shadowBaseLanguage);
        $structure->setUuid($contentNode->getPropertyValue('jcr:uuid'));
        $structure->setPath(str_replace($this->getContentNode($webspaceKey)->getPath(), '', $contentNode->getPath()));
        $structure->setNodeType(
            $contentNode->getPropertyValueWithDefault(
                $this->properties->getName('nodeType'),
                Structure::NODE_TYPE_CONTENT
            )
        );
        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($localization);
        $structure->setCreator($contentNode->getPropertyValueWithDefault($this->properties->getName('creator'), 0));
        $structure->setChanger($contentNode->getPropertyValueWithDefault($this->properties->getName('changer'), 0));
        $structure->setCreated(
            $contentNode->getPropertyValueWithDefault($this->properties->getName('created'), new \DateTime())
        );
        $structure->setChanged(
            $contentNode->getPropertyValueWithDefault($this->properties->getName('changed'), new \DateTime())
        );
        $structure->setHasChildren($contentNode->hasNodes());

        $structure->setNodeState(
            $contentNode->getPropertyValueWithDefault(
                $this->properties->getName('state'),
                StructureInterface::STATE_TEST
            )
        );
        $structure->setNavContexts(
            $contentNode->getPropertyValueWithDefault($this->properties->getName('navContexts'), array())
        );
        $structure->setPublished(
            $contentNode->getPropertyValueWithDefault($this->properties->getName('published'), null)
        );
        $structure->setOriginTemplate(
            $contentNode->getPropertyValueWithDefault($this->properties->getName('template'), $this->defaultTemplate)
        );
        $structure->setEnabledShadowLanguages(
            $this->getEnabledShadowLanguages($contentNode)
        );
        $structure->setConcreteLanguages(
            $this->getConcreteLanguages($contentNode)
        );

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties(true) as $property) {
            if (!($property instanceof SectionPropertyInterface)) {
                $type = $this->getContentType($property->getContentTypeName());
                $type->read(
                    $contentNode,
                    new TranslatedProperty(
                        $property,
                        $availableLocalization,
                        $this->languageNamespace
                    ),
                    $webspaceKey,
                    $availableLocalization,
                    null
                );
            }
        }

        // load data of extensions
        $data = array();
        foreach ($this->structureManager->getExtensions($structure->getKey()) as $extension) {
            $extension->setLanguageCode($localization, $this->languageNamespace, $this->internalPrefix);
            $data[$extension->getName()] = $extension->load($contentNode, $webspaceKey, $availableLocalization);
        }
        $structure->setExt($data);

        $this->loadInternalLinkDependencies(
            $structure,
            $localization,
            $webspaceKey,
            $loadGhostContent
        );

        // throw an content.node.load event (disabled for now)
        //$event = new ContentNodeEvent($contentNode, $structure);
        //$this->eventDispatcher->dispatch(ContentEvents::NODE_LOAD, $event);

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadByNode.mapping');
            $this->stopwatch->stop('contentManager.loadByNode');
        }

        return $structure;
    }

    /**
     * Determites locale for shadow-pages
     */
    private function getShadowLocale(NodeInterface $node, $defaultLocale)
    {
        $shadowOn = $node->getPropertyValueWithDefault($this->properties->getName('shadow-on'), false);
        $shadowBaseLanguage = null;
        if (true === $shadowOn) {
            $shadowBaseLanguage = $node->getPropertyValueWithDefault(
                $this->properties->getName('shadow-base'),
                false
            );

            if ($shadowBaseLanguage) {
                return $shadowBaseLanguage;
            }
        }

        return $defaultLocale;
    }

    /**
     * {@inheritdoc}
     */
    private function loadInternalLinkDependencies(
        StructureInterface $content,
        $localization,
        $webspaceKey,
        $loadGhostContent = false
    ) {
        if ($content->getNodeType() === Structure::NODE_TYPE_INTERNAL_LINK && $content->hasTag('sulu.rlp')) {
            $internal = $content->getPropertyValueByTagName('sulu.rlp');

            if (!empty($internal)) {
                $internalContent =
                    $this->load(
                        $internal,
                        $webspaceKey,
                        $localization,
                        $loadGhostContent
                    );
                if ($internalContent !== null) {
                    $content->setInternalLinkContent($internalContent);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadBreadcrumb($uuid, $languageCode, $webspaceKey)
    {
        $this->properties->setLanguage($languageCode);

        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.loadBreadcrumb');
            $this->stopwatch->start('contentManager.loadBreadcrumb.query');
        }

        $sql = sprintf(
            "SELECT parent.[jcr:uuid], child.[jcr:uuid]
             FROM [nt:unstructured] AS child INNER JOIN [nt:unstructured] AS parent
                 ON ISDESCENDANTNODE(child, parent)
             WHERE child.[jcr:uuid]='%s'",
            $uuid
        );

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadBreadcrumb.query');
        }

        $query = $this->createSql2Query($sql);
        $nodes = $query->execute();

        $result = array();
        $groundDepth = $this->getContentNode($webspaceKey)->getDepth();

        /** @var Row $row */
        foreach ($nodes->getRows() as $row) {
            $node = $row->getNode('parent');
            // uuid
            $nodeUuid = $node->getIdentifier();
            // depth
            $depth = $node->getDepth() - $groundDepth;
            if ($depth >= 0) {
                // title
                $templateKey = $node->getPropertyValueWithDefault(
                    $this->properties->getName('template'),
                    $this->defaultTemplate
                );
                $structure = $this->getStructure($templateKey);
                $nodeNameProperty = $structure->getPropertyByTagName('sulu.node.name');
                $property = $structure->getProperty($nodeNameProperty->getName());
                $type = $this->getContentType($property->getContentTypeName());
                $type->read(
                    $node,
                    new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                    $webspaceKey,
                    $languageCode,
                    null
                );
                $nodeName = $property->getValue();
                $structure->setUuid($node->getPropertyValue('jcr:uuid'));
                $structure->setPath(str_replace($this->getContentNode($webspaceKey)->getPath(), '', $node->getPath()));

                // throw an content.node.load event (disabled for now)
                //$event = new ContentNodeEvent($node, $structure);
                //$this->eventDispatcher->dispatch(ContentEvents::NODE_LOAD, $event);

                $result[$depth] = new BreadcrumbItem($depth, $nodeUuid, $nodeName);
            }
        }

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadBreadcrumb');
        }

        ksort($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($uuid, $webspaceKey)
    {
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        $this->deleteRecursively($contentNode);
        $session->save();
    }

    /**
     * {@inheritdoc}
     */
    public function move($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode)
    {
        return $this->copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode)
    {
        $result = $this->copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode, false);

        // session don't recognice a new child in parent, a refresh fixes that
        $this->getSession()->refresh(false);

        return $result;
    }

    public function orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $languageCode)
    {
        // prepare utility
        $session = $this->getSession();

        // load from phpcr
        /** @var NodeInterface $beforeTargetNode */
        /** @var NodeInterface $subjectNode */
        list($beforeTargetNode, $subjectNode) = iterator_to_array(
            $session->getNodesByIdentifier(array($uuid, $beforeUuid)),
            false
        );

        $parent = $beforeTargetNode->getParent();

        // reorder
        $parent->orderBefore($beforeTargetNode->getName(), $subjectNode->getName());

        // set changer of node in specific language
        $this->setChanger($beforeTargetNode, $userId, $languageCode);
        $this->setChanger($subjectNode, $userId, $languageCode);

        // save session
        $session->save();

        // session don't recognice a new child order, a refresh fixes that
        $session->refresh(false);

        return $this->load($uuid, $webspaceKey, $languageCode);
    }

    /**
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
     * copies (move = false) or move (move = true) the src (uuid) node to dest (parentUuid) node
     * @param string $uuid
     * @param string $destParentUuid
     * @param integer $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param bool $move
     * @return StructureInterface
     */
    private function copyOrMove($uuid, $destParentUuid, $userId, $webspaceKey, $languageCode, $move = true)
    {
        // find localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $localizations = $webspace->getAllLocalizations();

        // prepare utility
        $session = $this->getSession();

        // load from phpcr
        $node = $session->getNodeByIdentifier($uuid);
        $parentNode = $session->getNodeByIdentifier($destParentUuid);

        // prepare content node
        $content = $this->loadByNode($node, $languageCode, $webspaceKey, false, true);
        $nodeName = $content->getPropertyValueByTagName('sulu.node.name');
        $nodeName = $this->cleaner->cleanup($nodeName, $languageCode);
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
                $content->getNodeType() === Structure::NODE_TYPE_CONTENT
            ) {
                $this->adaptResourceLocator(
                    $content,
                    $node,
                    $parentResourceLocator,
                    $move,
                    $webspaceKey,
                    $locale->getLocalization()
                );

                // set changer of node
                $this->properties->setLanguage($languageCode);
                $node->setProperty($this->properties->getName('changer'), $userId);
                $node->setProperty($this->properties->getName('changed'), new DateTime());
            }
        }

        // set changer of node in specific language
        $this->setChanger($node, $userId, $languageCode);

        $session->save();

        return $this->loadByNode($node, $languageCode, $webspaceKey);
    }

    private function setChanger(NodeInterface $node, $userId, $languageCode)
    {
        $this->properties->setLanguage($languageCode);
        $node->setProperty($this->properties->getName('changer'), $userId);
        $node->setProperty($this->properties->getName('changed'), new DateTime());
    }

    /**
     * adopts resource locator for just moved or copied node
     * @param StructureInterface $content
     * @param NodeInterface $node
     * @param string $parentResourceLocator
     * @param boolean $move
     * @param string $webspaceKey
     * @param string $languageCode
     */
    private function adaptResourceLocator(
        StructureInterface $content,
        NodeInterface $node,
        $parentResourceLocator,
        $move,
        $webspaceKey,
        $languageCode
    ) {
        // get strategy
        $strategy = $this->getResourceLocator()->getStrategy();

        // get resource locator pathes
        $srcResourceLocator = $content->getPropertyValueByTagName('sulu.rlp');

        if ($srcResourceLocator !== null) {
            $resourceLocatorPart = PathHelper::getNodeName($srcResourceLocator);
        } else {
            $resourceLocatorPart = $content->getPropertyValueByTagName('sulu.node.name');
        }

        // generate new resourcelocator
        $destResourceLocator = $strategy->generate(
            $resourceLocatorPart,
            $parentResourceLocator,
            $webspaceKey,
            $languageCode
        );

        // move resourcelocator
        if ($move) {
            $strategy->move($srcResourceLocator, $destResourceLocator, $webspaceKey, $languageCode);
        } else {
            $strategy->save($node, $destResourceLocator, $webspaceKey, $languageCode);
        }
    }

    /**
     * remove node with references (path, history path ...)
     * @param NodeInterface $node
     */
    private function deleteRecursively(NodeInterface $node)
    {
        foreach ($node->getReferences() as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                $this->deleteRecursively($ref->getParent());
            } else {
                $this->deleteRecursively($ref);
            }
        }
        $node->remove();
    }

    /**
     * returns a structure with given key
     * @param string $key key of content type
     * @return StructureInterface
     */
    protected function getStructure($key)
    {
        return $this->structureManager->getStructure($key);
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
     * @return NodeInterface
     */
    protected function getContentNode($webspaceKey)
    {
        return $this->sessionManager->getContentNode($webspaceKey);
    }

    /**
     * @return SessionInterface
     */
    protected function getSession()
    {
        return $this->sessionManager->getSession();
    }

    /**
     * @param $webspaceKey
     * @param string $languageCode
     * @param string $segment
     * @return NodeInterface
     */
    protected function getRouteNode($webspaceKey, $languageCode, $segment)
    {
        return $this->sessionManager->getRouteNode($webspaceKey, $languageCode, $segment);
    }

    /**
     * @param $name
     * @param NodeInterface $parent
     * @return string
     */
    private function getUniquePath($name, NodeInterface $parent)
    {
        if ($parent->hasNode($name)) {
            $i = 0;
            do {
                $i++;
            } while ($parent->hasNode($name . '-' . $i));

            return $name . '-' . $i;
        } else {
            return $name;
        }
    }

    /**
     * initializes cache for extension data
     */
    private function initializeExtensionCache()
    {
        $this->extensionDataCache = new ArrayCache();
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
        $rootDepth = substr_count($this->sessionManager->getContentNode($webspaceKey)->getPath(), '/');

        $result = array();
        foreach ($locales as $locale) {
            $routesPath = $this->sessionManager->getRouteNode($webspaceKey, $locale)->getPath();

            /** @var \Jackalope\Query\Row $row */
            foreach ($queryResult->getRows() as $row) {
                $pageDepth = substr_count($row->getPath('page'), '/') - $rootDepth;

                if ($maxDepth === null || $maxDepth < 0 || ($maxDepth > 0 && $pageDepth <= $maxDepth)) {
                    $item = $this->rowToArray($row, $locale, $webspaceKey, $routesPath, $fields);

                    if (false !== $item && !in_array($item, $result)) {
                        $result[] = $item;
                    }
                }
            };
        }

        return $result;
    }

    /**
     * converts a query row to an array
     */
    private function rowToArray(Row $row, $locale, $webspaceKey, $routesPath, $fields)
    {
        // reset cache
        $this->initializeExtensionCache();
        $this->properties->setLanguage($locale);

        // check and determine shadow-nodes
        $node = $row->getNode('page');
        $locale = $this->getShadowLocale($node, $locale);
        $this->properties->setLanguage($locale);

        // load default data
        $uuid = $row->getValue('page.jcr:uuid');

        $templateKey = $node->getPropertyValue($this->properties->getName('template'));
        $nodeType = $node->getPropertyValue($this->properties->getName('nodeType'));

        $changed = $node->getPropertyValue($this->properties->getName('changed'));
        $changer = $node->getPropertyValue($this->properties->getName('changer'));
        $created = $node->getPropertyValue($this->properties->getName('created'));
        $creator = $node->getPropertyValue($this->properties->getName('creator'));

        if ($templateKey !== '') {
            $path = $row->getPath('page');

            // get structure
            $templateKey = $this->templateResolver->resolve($nodeType, $templateKey);
            $structure = $this->structureManager->getStructure($templateKey);

            // generate field data
            $fieldsData = $this->getFieldsData($row, $fields, $templateKey, $webspaceKey, $locale);

            return array_merge(
                array(
                    'uuid' => $uuid,
                    'nodeType' => $nodeType,
                    'path' => str_replace($this->sessionManager->getContentNode($webspaceKey)->getPath(), '', $path),
                    'changed' => $changed,
                    'changer' => $changer,
                    'created' => $created,
                    'creator' => $creator,
                    'title' => $this->getTitle($row, $structure, $locale),
                    'url' => $this->getUrl($path, $row, $structure, $webspaceKey, $locale, $routesPath),
                    'locale' => $locale,
                    'template' => $templateKey
                ),
                $fieldsData
            );
        }

        return false;
    }

    private function getFieldsData(Row $row, $fields, $templateKey, $webspaceKey, $locale)
    {
        $fieldsData = array();
        foreach ($fields[$locale] as $field) {
            // determine target for data in result array
            if (isset($fieldsData['target'])) {
                if (!isset($fieldsData[$field['target']])) {
                    $fieldsData[$field['target']] = array();
                }
                $target = & $fieldsData[$field['target']];
            } else {
                $target = & $fieldsData;
            }

            // create target
            if (!isset($target[$field['name']])) {
                $target[$field['name']] = '';
            }
            if (($data = $this->getFieldData($field, $row, $templateKey, $webspaceKey, $locale)) !== null) {
                $target[$field['name']] = $data;
            }
        }

        return $fieldsData;
    }

    private function getFieldData($field, Row $row, $templateKey, $webspaceKey, $locale)
    {
        if (!isset($field['property'])) {
            // normal data from node property
            return $row->getValue($field['column']);
        } elseif (!isset($field['extension']) && (!isset($field['templateKey']) || $field['templateKey'] === $templateKey)) {
            // not extension data but property of node
            return $this->getPropertyData($row->getNode('page'), $field['property'], $webspaceKey, $locale);
        } elseif (isset($field['extension'])) {
            // data from extension
            return $this->getExtensionData(
                $row->getNode('page'),
                $field['extension'],
                $field['property'],
                $webspaceKey,
                $locale
            );
        }

        return null;
    }

    /**
     * Returns data for property
     */
    private function getPropertyData(NodeInterface $node, PropertyInterface $property, $webspaceKey, $locale)
    {
        $contentType = $this->contentTypeManager->get($property->getContentTypeName());

        $contentType->read(
            $node,
            new TranslatedProperty($property, $locale, $this->languageNamespace),
            $webspaceKey,
            $locale,
            null
        );

        return $contentType->getContentData($property);
    }

    /**
     * Returns data for extension and property name
     */
    private function getExtensionData(
        NodeInterface $node,
        StructureExtension $extension,
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
     * load data from extension
     */
    private function loadExtensionData(NodeInterface $node, StructureExtension $extension, $webspaceKey, $locale)
    {
        $extension->setLanguageCode($locale, $this->languageNamespace, '');
        $data = $extension->load(
            $node,
            $webspaceKey,
            $locale
        );

        // insure array
        if ($data instanceof ArrayableInterface) {
            $data = $data->toArray();
        }

        return $data;
    }

    /**
     * Returns title of a row
     */
    private function getTitle(Row $row, StructureInterface $structure, $locale)
    {
        $property = new TranslatedProperty(
            $structure->getPropertyByTagName('sulu.node.name'),
            $locale,
            $this->languageNamespace
        );

        return $row->getValue('page.' . $property->getName());
    }

    /**
     * Returns url of a row
     */
    private function getUrl($path, Row $row, StructureInterface $structure, $webspaceKey, $locale, $routesPath)
    {
        $url = '';
        // if homepage
        if ($this->sessionManager->getContentNode($webspaceKey)->getPath() === $path) {
            $url = '/';
        } else {
            if ($structure->hasTag('sulu.rlp')) {
                $property = $structure->getPropertyByTagName('sulu.rlp');

                if ($property->getContentTypeName() !== 'resource_locator') {
                    $property = new TranslatedProperty(
                        $structure->getPropertyByTagName('sulu.rlp'),
                        $locale,
                        $this->languageNamespace
                    );
                    $url = $row->getValue('page.' . $property->getName());
                }
            }

            try {
                $routePath = $row->getPath('route');
                $url = str_replace($routesPath, '', $routePath);
            } catch (RepositoryException $ex) {
                // ignore exception because no route node exists
                // could have several reasons:
                //  - external links has text-line as "rlp"
                //  - internal links has a "reference" on another node
                //  - no url exists
            }
        }

        return $url;
    }
}
