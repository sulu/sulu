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
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\StructureExtension\StructureExtension;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\StructureType;
use Sulu\Component\Content\Template\TemplateResolverInterface;
use Sulu\Component\Content\Template\Exception\TemplateNotFoundException;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\NodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Sulu\Component\Content\Event\ContentOrderBeforeEvent;
use Sulu\Component\Util\SuluNodeHelper;
use PHPCR\PropertyType;
use Sulu\Component\Content\Event\ContentNodeDeleteEvent;

/**
 * Maps content nodes to phpcr nodes with content types and provides utility function to handle content nodes
 *
 * Short term todo:
 *
 * - Rename localization, languageCode, language etc. to "locale"
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

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    public function __construct(
        ContentTypeManager $contentTypeManager,
        StructureManagerInterface $structureManager,
        SessionManagerInterface $sessionManager,
        EventDispatcherInterface $eventDispatcher,
        LocalizationFinderInterface $localizationFinder,
        PathCleanupInterface $cleaner,
        WebspaceManagerInterface $webspaceManager,
        TemplateResolverInterface $templateResolver,
        SuluNodeHelper $nodeHelper,
        RlpStrategyInterface $strategy,
        $defaultLanguage,
        $defaultTemplates,
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
        $this->defaultTemplates = $defaultTemplates;
        $this->languageNamespace = $languageNamespace;
        $this->internalPrefix = $internalPrefix;
        $this->cleaner = $cleaner;
        $this->webspaceManager = $webspaceManager;
        $this->templateResolver = $templateResolver;
        $this->nodeHelper = $nodeHelper;
        $this->strategy = $strategy;

        // optional
        $this->stopwatch = $stopwatch;
    }

    /**
     * Create a new property translator
     *
     * @param string $languageCode
     * @param string $structureType
     *
     * @return MultipleTranslatedProperties
     */
    protected function createPropertyTranslator($languageCode, $structureType = Structure::TYPE_PAGE)
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

        $properties->setLanguage($languageCode);
        $properties->setStructureType($structureType);

        return $properties;
    }

    /**
     * {@inheritDoc}
     */
    public function saveRequest(ContentMapperRequest $request)
    {
        $this->validateRequest($request);

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
        $languageCode,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = null,
        $shadowBaseLanguage = null,
        $structureType = Structure::TYPE_PAGE
    ) {
        $propertyTranslator = $this->createPropertyTranslator($languageCode, $structureType);

        // set default node-type
        if (!isset($data['nodeType'])) {
            $data['nodeType'] = Structure::NODE_TYPE_CONTENT;
        }

        $resolvedTemplateKey = $this->templateResolver->resolve($data['nodeType'], $templateKey);
        $structure = $this->getStructure($resolvedTemplateKey, $structureType);

        $session = $this->getSession();

        if (Structure::TYPE_PAGE === $structureType) {
            if ($parentUuid !== null) {
                $root = $session->getNodeByIdentifier($parentUuid);
            } else {
                $root = $this->getContentNode($webspaceKey);
            }
        } else {
            $root = $this->sessionManager->getSnippetNode($resolvedTemplateKey);
        }

        $nodeNameProperty = $structure->getProperty('title');
        $translatedNodeNameProperty = new TranslatedProperty(
            $nodeNameProperty,
            $languageCode,
            $this->languageNamespace
        );

        $dateTime = new \DateTime();

        $newTranslatedNode = function (NodeInterface $node) use ($userId, $dateTime, &$state, $propertyTranslator) {
            $node->setProperty($propertyTranslator->getName('creator'), $userId);
            $node->setProperty($propertyTranslator->getName('created'), $dateTime);

            if (!isset($state)) {
                $state = StructureInterface::STATE_TEST;
            }
        };

        $nodeNamePropertyName = $nodeNameProperty->getName();
        if (!isset($data[$nodeNamePropertyName])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'You must set the "%s" data key',
                    $nodeNamePropertyName
                )
            );
        }

        // title should not have a slash
        $title = str_replace('/', '-', $data[$nodeNamePropertyName]);

        /** @var NodeInterface $node */
        if ($uuid === null) {
            // create a new node
            $path = $this->cleaner->cleanUp($title, $languageCode);
            $path = $this->getUniquePath($path, $root);
            $node = $root->addNode($path);
            $newTranslatedNode($node);

            $node->addMixin('sulu:' . $structureType);
        } else {
            $node = $session->getNodeByIdentifier($uuid);
            $nodeStructureType = $this->nodeHelper->getStructureTypeForNode($node);

            if (null !== $nodeStructureType && $nodeStructureType !== $structureType) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Cannot change the structure type of "%s" to "%s"',
                        $node->getPath(),
                        $structureType
                    )
                );
            }

            if (!$node->hasProperty($propertyTranslator->getName('title'))) {
                $newTranslatedNode($node);
            } else {
                $hasSameLanguage = ($languageCode == $this->defaultLanguage);

                $hasSamePath = ($node->getPath() !== $root->getPath());

                $hasDifferentTitle = !$node->hasProperty($translatedNodeNameProperty->getName()) ||
                    $node->getPropertyValue(
                        $translatedNodeNameProperty->getName()
                    ) !== $data[$nodeNameProperty->getName()];

                // decide if the snippet template type has changed. If it has then we need to
                // move it to the path of the new template type.
                if ($structureType === Structure::TYPE_SNIPPET) {
                    $currentSnippetType = $this->nodeHelper->extractSnippetTypeFromPath($node->getPath());
                    if ($templateKey !== $currentSnippetType) {
                        $session->move(
                            $node->getPath(),
                            $root->getPath() . '/' . PathHelper::getNodeName($node->getPath())
                        );
                    }
                }

                if (!$this->noRenamingFlag && $hasSameLanguage && $hasSamePath && $hasDifferentTitle) {
                    $path = $this->cleaner->cleanUp($title, $languageCode);
                    $path = $this->getUniquePath($path, $node->getParent());

                    if ($path) {
                        // workaround for Jackalope bug referenced in: https://github.com/sulu-cmf/sulu/issues/518
                        $nextNode = $this->nodeHelper->getNextNode($node);

                        if ($nextNode) {
                            $nextNodeName = $nextNode->getName();
                            $nodeName = $node->getName();
                        }

                        $node->rename($path);

                        if ($nextNode) {
                            $parentNode = $node->getParent();
                            $parentNode->orderBefore($node->getName(), $nextNodeName);
                        }
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

        if ($node->hasProperty($propertyTranslator->getName('shadow-on'))) {
            $oldShadowStatus = $node->getPropertyValue($propertyTranslator->getName('shadow-on'));
            if ($isShadow !== $oldShadowStatus) {
                $shadowChanged = true;
            }
        }

        $node->setProperty($propertyTranslator->getName('changer'), $userId);
        $node->setProperty($propertyTranslator->getName('changed'), $dateTime);
        $node->setProperty($propertyTranslator->getName('template'), $templateKey);
        $node->setProperty($propertyTranslator->getName('shadow-on'), $isShadow);
        $node->setProperty($propertyTranslator->getName('shadow-base'), $shadowBaseLanguage);

        if (isset($data['nodeType'])) {
            $node->setProperty($propertyTranslator->getName('nodeType'), $data['nodeType']);
        }

        if (Structure::TYPE_PAGE === $structureType) {
            // do not state transition for root (contents) node
            $contentRootNode = $this->getContentNode($webspaceKey);
            if ($node->getPath() !== $contentRootNode->getPath() && isset($state)) {
                $this->changeState(
                    $node,
                    $state,
                    $structure,
                    $propertyTranslator->getName('state'),
                    $propertyTranslator->getName('published')
                );
            }
        } elseif (isset($state)) {
            $this->changeState(
                $node,
                $state,
                $structure,
                $propertyTranslator->getName('state'),
                $propertyTranslator->getName('published')
            );
        }

        if (Structure::TYPE_PAGE === $structureType) {
            if (isset($data['navContexts']) && $data['navContexts'] !== false
                && $this->validateNavContexts(
                    $data['navContexts'],
                    $this->webspaceManager->findWebspaceByKey($webspaceKey)
                )
            ) {
                $node->setProperty($propertyTranslator->getName('navContexts'), $data['navContexts']);
            }
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
                        $translatedProperty = new TranslatedProperty($property, $languageCode, $this->languageNamespace);
                        // TODO find a better why for change Types (same hack is used in BlockContentType:write )
                        $type->remove(
                            $node,
                            $translatedProperty,
                            $webspaceKey,
                            $languageCode,
                            null
                        );
                        $type->write(
                            $node,
                            $translatedProperty,
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

        if (Structure::TYPE_PAGE === $structureType && false === $shadowChanged) {
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

        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_PRE_SAVE, $event);

        $session->save();

        $structure->setUuid($node->getPropertyValue('jcr:uuid'));
        $structure->setNodeType(
            $node->getPropertyValueWithDefault($propertyTranslator->getName('nodeType'), Structure::NODE_TYPE_CONTENT)
        );
        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($languageCode);
        $structure->setCreator($node->getPropertyValue($propertyTranslator->getName('creator')));
        $structure->setChanger($node->getPropertyValue($propertyTranslator->getName('changer')));
        $structure->setCreated($node->getPropertyValue($propertyTranslator->getName('created')));
        $structure->setChanged($node->getPropertyValue($propertyTranslator->getName('changed')));
        $structure->setIsShadow($node->getPropertyValueWithDefault($propertyTranslator->getName('shadow-on'), false));
        $structure->setShadowBaseLanguage(
            $node->getPropertyValueWithDefault($propertyTranslator->getName('shadow-base'), null)
        );
        $structure->setEnabledShadowLanguages(
            $this->getEnabledShadowLanguages($node)
        );
        $structure->setConcreteLanguages($this->getConcreteLanguages($node));

        $structure->setPublished(
            $node->getPropertyValueWithDefault($propertyTranslator->getName('published'), null)
        );

        if (Structure::TYPE_PAGE === $structureType) {
            $structure->setPath(
                str_replace($this->sessionManager->getContentPath($webspaceKey), '', $node->getPath())
            );
            $structure->setNavContexts(
                $node->getPropertyValueWithDefault($propertyTranslator->getName('navContexts'), array())
            );
            $structure->setOriginTemplate(
                $node->getPropertyValueWithDefault(
                    $propertyTranslator->getName('template'),
                    $this->defaultTemplates[$structureType]
                )
            );
            // load dependencies for internal links
            $this->loadInternalLinkDependencies(
                $structure,
                $languageCode,
                $webspaceKey
            );

            $this->loadLocalizedUrlsForPage($structure, $node, $webspaceKey, null);
        }

        // throw an content.node.save event
        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_SAVE, $event);

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
        $nodeLanguages = $this->nodeHelper->getLanguagesForNode($node);
        $shadowBaseLanguages = array();

        foreach ($nodeLanguages as $nodeLanguage) {
            $propertyTranslator = $this->createPropertyTranslator($nodeLanguage);
            $shadowOn = $node->getPropertyValueWithDefault($propertyTranslator->getName('shadow-on'), null);

            if ($shadowOn) {
                $nodeShadowBaseLanguage = $node->getPropertyValueWithDefault(
                    $propertyTranslator->getName('shadow-base'),
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
        $enabledLanguages = $this->nodeHelper->getLanguagesForNode($node);
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
        $propertyTranslator = $this->createPropertyTranslator($languageCode);

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
        $node->setProperty($propertyTranslator->getName('changer'), $userId);
        $node->setProperty($propertyTranslator->getName('changed'), $dateTime);

        // save data of extensions
        $extension = $this->structureManager->getExtension($structure->getKey(), $extensionName);
        $extension->save($node, $data, $webspaceKey, $languageCode);
        $ext[$extension->getName()] = $extension->load($node, $webspaceKey, $languageCode);

        $ext = array_merge($structure->getExt(), $ext);
        $structure->setExt($ext);

        $session->save();

        // throw an content.node.save event
        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_POST_SAVE, $event);

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
            $oldState = intval($oldState);
            $state = intval($state);

            if ($oldState === $state) {
                $structure->setNodeState($state);

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
        $query = $this->createSql2Query($sql2, $limit);

        return $this->loadByQuery($query, $languageCode, $webspaceKey);
    }

    /**
     * {@inheritDoc}
     */
    public function loadByQuery(
        QueryInterface $query,
        $languageCode,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        $result = $query->execute();
        $structures = array();

        foreach ($result as $row) {
            try {
                $structure = $this->loadByNode(
                    $row->getNode(),
                    $languageCode,
                    $webspaceKey,
                    $excludeGhost,
                    $loadGhostContent
                );
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
        $webspaceKey = null,
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
        $structureType = $this->nodeHelper->getStructureTypeForNode($contentNode) ? : Structure::TYPE_PAGE;
        $propertyTranslator = $this->createPropertyTranslator($localization, $structureType);

        $nodeType = $contentNode->getPropertyValueWithDefault(
            $propertyTranslator->getName('nodeType'),
            Structure::NODE_TYPE_CONTENT
        );

        $originTemplateKey = $this->defaultTemplates[$structureType];
        $templateKey = $contentNode->getPropertyValueWithDefault(
            $propertyTranslator->getName('template'),
            $originTemplateKey
        );

        $templateKey = $this->templateResolver->resolve($nodeType, $templateKey);
        $structure = $this->getStructure($templateKey, $structureType);

        $structure->setUuid($contentNode->getPropertyValue('jcr:uuid'));
        $structure->setNodeType(
            $contentNode->getPropertyValueWithDefault(
                $propertyTranslator->getName('nodeType'),
                Structure::NODE_TYPE_CONTENT
            )
        );
        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($localization);
        $structure->setCreator($contentNode->getPropertyValueWithDefault($propertyTranslator->getName('creator'), 0));
        $structure->setChanger($contentNode->getPropertyValueWithDefault($propertyTranslator->getName('changer'), 0));
        $structure->setCreated(
            $contentNode->getPropertyValueWithDefault($propertyTranslator->getName('created'), new \DateTime())
        );
        $structure->setChanged(
            $contentNode->getPropertyValueWithDefault($propertyTranslator->getName('changed'), new \DateTime())
        );
        $structure->setHasChildren($contentNode->hasNodes());
        $structure->setEnabledShadowLanguages(
            $this->getEnabledShadowLanguages($contentNode)
        );
        $structure->setConcreteLanguages(
            $this->getConcreteLanguages($contentNode)
        );

        return $structure;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByNode(
        NodeInterface $contentNode,
        $localization,
        $webspaceKey = null,
        $excludeGhost = true,
        $loadGhostContent = false,
        $excludeShadow = true
    ) {
        $structureType = $this->nodeHelper->getStructureTypeForNode($contentNode) ? : Structure::TYPE_PAGE;
        $propertyTranslator = $this->createPropertyTranslator($localization, $structureType);

        // START: getAvailableLocalization
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

        // if there was no webspace then determine the webspace from the content node path
        if (null === $webspaceKey) {
            $webspaceKey = $this->nodeHelper->extractWebspaceFromPath($contentNode->getPath());
        }

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.loadByNode.available-localization');
            $this->stopwatch->start('contentManager.loadByNode.mapping');
        }

        $shadowOn = $contentNode->getPropertyValueWithDefault($propertyTranslator->getName('shadow-on'), false);
        $shadowBaseLanguage = $contentNode->getPropertyValueWithDefault(
            $propertyTranslator->getName('shadow-base'),
            false
        );

        $availableLocalization = $this->getShadowLocale($contentNode, $availableLocalization);
        // END: getAvailableLocalization

        if (($excludeGhost && $excludeShadow) && $availableLocalization != $localization) {
            return null;
        }

        // now switch the language to the available localization
        if ($availableLocalization != $localization) {
            $propertyTranslator->setLanguage($availableLocalization);
        }

        $nodeType = $contentNode->getPropertyValueWithDefault(
            $propertyTranslator->getName('nodeType'),
            Structure::NODE_TYPE_CONTENT
        );

        $originTemplateKey = $this->defaultTemplates[$structureType];
        $templateKey = $contentNode->getPropertyValueWithDefault(
            $propertyTranslator->getName('template'),
            $originTemplateKey
        );

        $templateKey = $this->templateResolver->resolve($nodeType, $templateKey);
        $structure = $this->getStructure($templateKey, $structureType);

        // set structure to ghost, if the available localization does not match the requested one
        if ($availableLocalization != $localization) {
            if ($shadowBaseLanguage) {
                $structure->setType(StructureType::getShadow($availableLocalization));
            } else {
                $structure->setType(StructureType::getGhost($availableLocalization));
            }
        }

        $structure->setHasTranslation($contentNode->hasProperty($propertyTranslator->getName('title')));

        $structure->setIsShadow($shadowOn);
        $structure->setShadowBaseLanguage($shadowBaseLanguage);
        $structure->setUuid($contentNode->getPropertyValue('jcr:uuid'));
        $structure->setNodeType(
            $contentNode->getPropertyValueWithDefault(
                $propertyTranslator->getName('nodeType'),
                Structure::NODE_TYPE_CONTENT
            )
        );
        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($localization);
        $structure->setCreator($contentNode->getPropertyValueWithDefault($propertyTranslator->getName('creator'), 0));
        $structure->setChanger($contentNode->getPropertyValueWithDefault($propertyTranslator->getName('changer'), 0));
        $structure->setCreated(
            $contentNode->getPropertyValueWithDefault($propertyTranslator->getName('created'), new \DateTime())
        );
        $structure->setChanged(
            $contentNode->getPropertyValueWithDefault($propertyTranslator->getName('changed'), new \DateTime())
        );
        $structure->setHasChildren($contentNode->hasNodes());
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

        $structure->setNodeState(
            $contentNode->getPropertyValueWithDefault(
                $propertyTranslator->getName('state'),
                StructureInterface::STATE_TEST
            )
        );

        if ($structureType === Structure::TYPE_PAGE) {
            $structure->setPath(
                str_replace($this->sessionManager->getContentPath($webspaceKey), '', $contentNode->getPath())
            );
            $structure->setNavContexts(
                $contentNode->getPropertyValueWithDefault($propertyTranslator->getName('navContexts'), array())
            );
            $structure->setPublished(
                $contentNode->getPropertyValueWithDefault($propertyTranslator->getName('published'), null)
            );
            $structure->setOriginTemplate(
                $contentNode->getPropertyValueWithDefault(
                    $propertyTranslator->getName('template'),
                    $this->defaultTemplates[$structureType]
                )
            );

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

            $this->loadLocalizedUrlsForPage($structure, $contentNode, $webspaceKey, null);
        }

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
        $propertyTranslator = $this->createPropertyTranslator($defaultLocale);
        $shadowOn = $node->getPropertyValueWithDefault($propertyTranslator->getName('shadow-on'), false);
        $shadowBaseLanguage = null;
        if (true === $shadowOn) {
            $shadowBaseLanguage = $node->getPropertyValueWithDefault(
                $propertyTranslator->getName('shadow-base'),
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
        $propertyTranslator = $this->createPropertyTranslator($languageCode);

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
                    $propertyTranslator->getName('template'),
                    $this->defaultTemplates[Structure::TYPE_PAGE]
                );
                $structure = $this->getStructure($templateKey);
                $nodeNameProperty = $structure->getProperty('title');
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
                $structure->setPath(
                    str_replace($this->sessionManager->getContentPath($webspaceKey), '', $node->getPath())
                );

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
     * Loads urls for given page for all locales in webspace
     * @param Page $page
     * @param NodeInterface $node
     * @param string $webspaceKey
     * @param string $segmentKey
     */
    private function loadLocalizedUrlsForPage(Page $page, NodeInterface $node, $webspaceKey, $segmentKey)
    {
        $localizedUrls = array();

        if ($page->hasTag('sulu.rlp')) {
            $localizedUrls = $this->getLocalizedUrlsForPage($page, $node, $webspaceKey, $segmentKey);
        }

        $page->setUrls($localizedUrls);
    }

    /**
     * Returns urls for given page for all locales in webspace
     * @param Page $page
     * @param NodeInterface $node
     * @param string $webspaceKey
     * @param string $segmentKey
     * @return array
     */
    private function getLocalizedUrlsForPage(Page $page, NodeInterface $node, $webspaceKey, $segmentKey)
    {
        $localizedUrls = array();

        if (null === $webspaceKey) {
            $webspaceKey = $this->nodeHelper->extractWebspaceFromPath($node->getPath());
        }

        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $property = $page->getPropertyByTagName('sulu.rlp');
        $property = clone $property;

        $contentType = $this->contentTypeManager->get($property->getContentTypeName());

        foreach ($webspace->getAllLocalizations() as $localization) {

            // prepare translation vars
            $locale = $localization->getLocalization();
            $translatedProperty = new TranslatedProperty($property, $locale, $this->languageNamespace);

            // state property
            $propertyTranslator = $this->createPropertyTranslator($localization);
            $statePropertyName = $propertyTranslator->getName('state');

            if ($node->getPropertyValueWithDefault($statePropertyName, Structure::STATE_TEST) === Structure::STATE_PUBLISHED) {
                // set default value
                $property->setValue(null);
                $contentType->read($node, $translatedProperty, $webspaceKey, $locale, $segmentKey);

                if (null !== $property->getValue()) {
                    $localizedUrls[$locale] = $property->getValue();
                }
            }
        }

        return $localizedUrls;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($uuid, $webspaceKey, $dereference = false)
    {
        $session = $this->getSession();
        $node = $session->getNodeByIdentifier($uuid);

        $this->deleteRecursively($node, $webspaceKey, $dereference);
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

    /**
     * {@inheritDoc}
     */
    public function copyLanguage(
        $uuid,
        $userId,
        $webspaceKey,
        $srcLanguageCode,
        $destLanguageCodes,
        $structureType = Structure::TYPE_PAGE
    ) {
        if (!is_array($destLanguageCodes)) {
            $destLanguageCodes = array($destLanguageCodes);
        }

        $structure = $this->load($uuid, $webspaceKey, $srcLanguageCode);

        $data = $structure->toArray(true);

        foreach($destLanguageCodes as $destLanguageCode) {
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

        $event = new ContentOrderBeforeEvent($subjectNode, $beforeTargetNode);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_ORDER_BEFORE, $event);

        // save session
        $session->save();

        // session don't recognice a new child order, a refresh fixes that
        $session->refresh(false);

        return $this->load($uuid, $webspaceKey, $languageCode);
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
        $propertyTranslator = $this->createPropertyTranslator($languageCode);
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
        $nodeName = $content->getPropertyValue('title');

        // node name should not have a slash
        $nodeName = str_replace('/', '-', $nodeName);

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
                    $webspaceKey,
                    $locale->getLocalization(),
                    $userId
                );

                // set changer of node
                $propertyTranslator->setLanguage($languageCode);
                $node->setProperty($propertyTranslator->getName('changer'), $userId);
                $node->setProperty($propertyTranslator->getName('changed'), new DateTime());
            }
        }

        // set changer of node in specific language
        $this->setChanger($node, $userId, $languageCode);

        $session->save();

        return $this->loadByNode($node, $languageCode, $webspaceKey);
    }

    private function setChanger(NodeInterface $node, $userId, $languageCode)
    {
        $propertyTranslator = $this->createPropertyTranslator($languageCode);
        $node->setProperty($propertyTranslator->getName('changer'), $userId);
        $node->setProperty($propertyTranslator->getName('changed'), new DateTime());
    }

    /**
     * adopts resource locator for just moved or copied node
     * @param StructureInterface $content
     * @param NodeInterface $node
     * @param string $parentResourceLocator
     * @param string $webspaceKey
     * @param string $languageCode
     * @param int $userId
     */
    private function adaptResourceLocator(
        StructureInterface $content,
        NodeInterface $node,
        $parentResourceLocator,
        $webspaceKey,
        $languageCode,
        $userId
    ) {
        // prepare objects
        $property = $content->getPropertyByTagName('sulu.rlp');
        $translatedProperty = new TranslatedProperty($property, $languageCode, $this->languageNamespace);
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
            $languageCode
        );

        // save new resource-locator
        $property->setValue($destResourceLocator);
        $contentType->write($node, $translatedProperty, $userId, $webspaceKey, $languageCode, null);
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
     * returns a structure with given key
     * @param string $key key of content type
     * @return StructureInterface
     */
    protected function getStructure($key, $type = Structure::TYPE_PAGE)
    {
        $structure = $this->structureManager->getStructure($key, $type);

        if (!$structure) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Could not find "%s" structure template for key "%s"',
                    $type,
                    $key
                )
            );
        }

        return $structure;
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

    // =================================
    // START: Row to array mapping logic
    // =================================

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
        $rootDepth = substr_count($this->sessionManager->getContentPath($webspaceKey), '/');

        $result = array();
        foreach ($locales as $locale) {
            /** @var \Jackalope\Query\Row $row */
            foreach ($queryResult->getRows() as $row) {
                $pageDepth = substr_count($row->getPath('page'), '/') - $rootDepth;

                if ($maxDepth === null || $maxDepth < 0 || ($maxDepth > 0 && $pageDepth <= $maxDepth)) {
                    $item = $this->rowToArray($row, $locale, $webspaceKey, $fields);

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
    private function rowToArray(Row $row, $locale, $webspaceKey, $fields)
    {
        // reset cache
        $this->initializeExtensionCache();
        $propertyTranslator = $this->createPropertyTranslator($locale);

        // check and determine shadow-nodes
        $node = $row->getNode('page');
        if (
            $node->hasProperty($propertyTranslator->getName('template')) &&
            $node->hasProperty($propertyTranslator->getName('nodeType'))
        ) {
            if (
                $node->getPropertyValue($propertyTranslator->getName('nodeType')) === Structure::NODE_TYPE_INTERNAL_LINK
            ) {
                $nodeType = $node->getPropertyValue($propertyTranslator->getName('nodeType'));
                $parent = $node->getParent()->getIdentifier();

                // get structure (without data)
                $templateKey = $node->getPropertyValue($propertyTranslator->getName('template'));
                $templateKey = $this->templateResolver->resolve($nodeType, $templateKey);
                $structure = $this->structureManager->getStructure($templateKey);

                $property = new TranslatedProperty(
                    $structure->getPropertyByTagName('sulu.rlp'),
                    $locale,
                    $this->languageNamespace
                );
                $uuid = $node->getPropertyValue($property->getName());

                $node = $this->sessionManager->getSession()->getNodeByIdentifier($uuid);
                $structure = $this->load($uuid, $webspaceKey, $locale);
                $url = $structure->getResourceLocator();
            }

            $originLocale = $locale;
            $locale = $this->getShadowLocale($node, $locale);
            $propertyTranslator->setLanguage($locale);

            // load default data
            $uuid = $node->getIdentifier();

            $templateKey = $node->getPropertyValue($propertyTranslator->getName('template'));

            // if nodetype is set before (internal link)
            if (!isset($nodeType)) {
                $nodeType = $node->getPropertyValue($propertyTranslator->getName('nodeType'));
            }

            // if parent is set before (internal link)
            if (!isset($parent)) {
                $parent = $node->getParent()->getIdentifier();
            }

            $nodeState = $node->getPropertyValue($propertyTranslator->getName('state'));

            // if page is not piblished ignore it
            if ($nodeState !== Structure::STATE_PUBLISHED) {
                return false;
            }

            $changed = $node->getPropertyValue($propertyTranslator->getName('changed'));
            $changer = $node->getPropertyValue($propertyTranslator->getName('changer'));
            $created = $node->getPropertyValue($propertyTranslator->getName('created'));
            $creator = $node->getPropertyValue($propertyTranslator->getName('creator'));
            $published = $node->getPropertyValueWithDefault($propertyTranslator->getName('published'), null);

            $path = $row->getPath('page');

            // get structure
            $templateKey = $this->templateResolver->resolve(
                $node->getPropertyValue($propertyTranslator->getName('nodeType')),
                $templateKey
            );
            $structure = $this->structureManager->getStructure($templateKey);

            if (!isset($url)) {
                $url = $this->getUrl($path, $row, $structure, $webspaceKey, $originLocale);
            }

            // get url returns false if route is not this language
            if ($url !== false) {
                // generate field data
                $fieldsData = $this->getFieldsData(
                    $row,
                    $node,
                    $fields[$originLocale],
                    $templateKey,
                    $webspaceKey,
                    $locale
                );

                $key = $this->nodeHelper->extractWebspaceFromPath($path);
                $shortPath = str_replace($this->sessionManager->getContentPath($key), '', $path);

                return array_merge(
                    array(
                        'uuid' => $uuid,
                        'nodeType' => $nodeType,
                        'path' => $shortPath,
                        'changed' => $changed,
                        'changer' => $changer,
                        'created' => $created,
                        'published' => $published,
                        'creator' => $creator,
                        'title' => $this->getTitle($node, $structure, $webspaceKey, $locale),
                        'url' => $url,
                        'urls' => $this->getLocalizedUrlsForPage($structure, $node, $webspaceKey, null),
                        'locale' => $locale,
                        'webspaceKey' => $key,
                        'template' => $templateKey,
                        'parent' => $parent,
                        'order' => $node->hasProperty('sulu:order') ? $node->getPropertyValue('sulu:order') : null,
                    ),
                    $fieldsData
                );
            }
        }

        return false;
    }

    /**
     * Return extracted data (configured by fields array) from node
     */
    private function getFieldsData(Row $row, NodeInterface $node, $fields, $templateKey, $webspaceKey, $locale)
    {
        $fieldsData = array();
        foreach ($fields as $field) {
            // determine target for data in result array
            if (isset($field['target'])) {
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
            if (($data = $this->getFieldData($field, $row, $node, $templateKey, $webspaceKey, $locale)) !== null) {
                $target[$field['name']] = $data;
            }
        }

        return $fieldsData;
    }

    /**
     * Return data for one field
     */
    private function getFieldData($field, Row $row, NodeInterface $node, $templateKey, $webspaceKey, $locale)
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
            return $this->getPropertyData($node, $field['property'], $webspaceKey, $locale);
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

        return $extension->getContentData($data);
    }

    /**
     * Returns title of a row
     */
    private function getTitle(NodeInterface $node, StructureInterface $structure, $webspaceKey, $locale)
    {
        return $this->getPropertyData($node, $structure->getProperty('title'), $webspaceKey, $locale);
    }

    /**
     * Returns url of a row
     */
    private function getUrl(
        $path,
        Row $row,
        StructureInterface $structure,
        $webspaceKey,
        $locale
    ) {
        // if homepage
        if ($webspaceKey !== null && $this->sessionManager->getContentPath($webspaceKey) === $path) {
            $url = '/';
        } else {
            if ($structure->hasTag('sulu.rlp')) {
                $property = $structure->getPropertyByTagName('sulu.rlp');

                if ($property->getContentTypeName() !== 'resource_locator') {
                    $url = 'http://' . $this->getPropertyData($row->getNode('page'), $property, $webspaceKey, $locale);
                } else {
                    $url = $this->getPropertyData($row->getNode('page'), $property, $webspaceKey, $locale);
                }
            } else {
                $url = '';
            }
        }

        return $url;
    }

    // ===============================
    // END: Row to array mapping logic
    // ===============================

    /**
     * {@inheritdoc}
     */
    public function restoreHistoryPath($path, $userId, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->strategy->restoreByPath($path, $webspaceKey, $languageCode, $segmentKey);

        $content = $this->loadByResourceLocator($path, $webspaceKey, $languageCode, $segmentKey);
        $property = $content->getPropertyByTagName('sulu.rlp');
        $property->setValue($path);

        $node = $this->sessionManager->getSession()->getNodeByIdentifier($content->getUuid());

        $contentType = $this->contentTypeManager->get($property->getContentTypeName());
        $contentType->write(
            $node,
            new TranslatedProperty($property, $languageCode, $this->languageNamespace),
            $userId,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        $this->sessionManager->getSession()->save();
    }

    /**
     * Validate the content mapper request
     *
     * TODO: We should be validating the domain object, i.e. the Page or Snippet structure
     *       types. But this requires refactoring the ContentMapper
     */
    private function validateRequest(ContentMapperRequest $request)
    {
        $this->validateRequired(
            $request,
            array(
                'templateKey',
                'userId',
                'locale',
                'type',
            )
        );

        if ($request->getType() === Structure::TYPE_PAGE) {
            $this->validateRequired(
                $request,
                array(
                    'webspaceKey',
                )
            );
        }
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
}
