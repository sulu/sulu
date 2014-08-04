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
use PHPCR\NodeInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\BreadcrumbItem;
use Sulu\Component\Content\BreadcrumbItemInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\ContentEvents;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Sulu\Component\Content\Exception\MandatoryPropertyException;
use Sulu\Component\Content\Exception\StateNotFoundException;
use Sulu\Component\Content\Exception\TranslatedNodeNotFoundException;
use Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Section\SectionPropertyInterface;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\StructureType;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

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
     * excepted states
     * @var array
     */
    private $states = array(
        StructureInterface::STATE_PUBLISHED,
        StructureInterface::STATE_TEST
    );

    private $properties;

    public function __construct(
        ContentTypeManager $contentTypeManager,
        StructureManagerInterface $structureManager,
        SessionManagerInterface $sessionManager,
        EventDispatcherInterface $eventDispatcher,
        LocalizationFinderInterface $localizationFinder,
        PathCleanupInterface $cleaner,
        $defaultLanguage,
        $defaultTemplate,
        $stopwatch = null
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
        $this->sessionManager = $sessionManager;
        $this->localizationFinder = $localizationFinder;
        $this->eventDispatcher = $eventDispatcher;
        $this->defaultLanguage = $defaultLanguage;
        $this->defaultTemplate = $defaultTemplate;
        $this->cleaner = $cleaner;

        // optional
        $this->stopwatch = $stopwatch;
    }

    /**
     * saves the given data in the content storage
     * @param array $data The data to be saved
     * @param string $templateKey Name of template
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Save data for given language
     * @param int $userId The id of the user who saves
     * @param bool $partialUpdate ignore missing property
     * @param string $uuid uuid of node if exists
     * @param string $parentUuid uuid of parent node
     * @param int $state state of node
     * @param string $showInNavigation
     *
     * @throws \Exception
     * @return StructureInterface
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
        $showInNavigation = null
    ) 
    {
        $structure = $this->getStructure($templateKey);

        $this->contentWriter->writeToStructure(
            $structure,
            $templateKey,
            $webspaceKey,
            $languageCode,
            $userId,
            $partialUpdate,
            $uuid,
            $parentUuid
        );

        // @todo: Move the structure hydration somewhere else.
        $structure->setUuid($node->getPropertyValue('jcr:uuid'));
        $structure->setPath(str_replace($this->getContentNode($webspaceKey)->getPath(), '', $node->getPath()));
        $structure->setNodeType($node->getTranslatedPropertyValue('nodeType', Structure::NODE_TYPE_CONTENT));
        $structure->setWebspaceKey($webspaceKey);
        $structure->setLanguageCode($languageCode);
        $structure->setCreator($node->getTranslatedPropertyValue('creator'));
        $structure->setChanger($node->getTranslatedPropertyValue('changer'));
        $structure->setCreated($node->getTranslatedPropertyValue('created'));
        $structure->setChanged($node->getTranslatedPropertyValue('changed'));

        $structure->setNavigation(
            $node->getTranslatedPropertyValue('navigation', false)
        );
        $structure->setGlobalState(
            $this->getInheritedState($node, 'state', $webspaceKey)
        );
        $structure->setPublished(
            $node->getTranslatedPropertyValue('published', null)
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
     * save a extension with given name and data to an existing node
     * @param string $uuid
     * @param array $data
     * @param string $extensionName
     * @param string $webspaceKey
     * @param string $languageCode
     * @param integer $userId
     * @throws \Sulu\Component\Content\Exception\TranslatedNodeNotFoundException
     * @return StructureInterface
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
        $structure->getExtension($extensionName);

        // set changer / changed
        $dateTime = new \DateTime();
        $node->setProperty($this->properties->getName('changer'), $userId);
        $node->setProperty($this->properties->getName('changed'), $dateTime);

        // save data of extensions
        $structure->getExtension($extensionName)->save($node, $data, $webspaceKey, $languageCode);
        $session->save();

        // throw an content.node.save event
        $event = new ContentNodeEvent($node, $structure);
        $this->eventDispatcher->dispatch(ContentEvents::NODE_SAVE, $event);

        return $structure;
    }


    /**
     * saves the given data in the content storage
     * @param array $data The data to be saved
     * @param string $templateKey Name of template
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Save data for given language
     * @param int $userId The id of the user who saves
     * @param bool $partialUpdate ignore missing property
     *
     * @throws \PHPCR\ItemExistsException if new title already exists
     *
     * @return StructureInterface
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
            StructureInterface::STATE_PUBLISHED,
            true
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
     * returns a list of data from children of given node
     * @param NodeInterface $parent
     * @param $webspaceKey
     * @param $languageCode
     * @param int $depth
     * @param bool $flat
     * @param bool $ignoreExceptions
     * @param bool $excludeGhosts If true ghost pages are also loaded
     * @throws \Exception
     * @return array
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
     * returns the data from the given id
     * @param string $uuid UUID of the content
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Read data for given language
     * @param bool $loadGhostContent True if also a ghost page should be returned, otherwise false
     * @return StructureInterface
     */
    public function load($uuid, $webspaceKey, $languageCode, $loadGhostContent = false)
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.load');
        }
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        $result = $this->loadByNode($contentNode, $languageCode, $webspaceKey, false, $loadGhostContent);

        if ($this->stopwatch) {
            $this->stopwatch->stop('contentManager.load');
        }

        return $result;
    }

    /**
     * returns the data from the given id
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function loadStartPage($webspaceKey, $languageCode)
    {
        $uuid = $this->getContentNode($webspaceKey)->getIdentifier();

        $startPage = $this->load($uuid, $webspaceKey, $languageCode);
        $startPage->setNodeState(StructureInterface::STATE_PUBLISHED);
        $startPage->setGlobalState(StructureInterface::STATE_PUBLISHED);
        $startPage->setNavigation(true);

        return $startPage;
    }

    /**
     * returns data from given path
     * @param string $resourceLocator Resource locator
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode
     * @param string $segmentKey
     * @return StructureInterface
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

        return $this->loadByNode($contentNode, $languageCode, $webspaceKey);
    }

    /**
     * returns the content returned by the given sql2 query as structures
     * @param string $sql2 The query, which returns the content
     * @param string $languageCode The language code
     * @param string $webspaceKey The webspace key
     * @param int $limit Limits the number of returned rows
     * @return StructureInterface[]
     */
    public function loadBySql2($sql2, $languageCode, $webspaceKey, $limit = null)
    {
        $structures = array();

        $query = $this->createSql2Query($sql2, $limit);
        $result = $query->execute();

        foreach ($result->getNodes() as $node) {
            $structures[] = $this->loadByNode($node, $languageCode, $webspaceKey);
        }

        return $structures;
    }

    /**
     * load tree from root to given path
     * @param string $uuid
     * @param string $languageCode
     * @param string $webspaceKey
     * @param bool $excludeGhost
     * @param bool $loadGhostContent
     * @return StructureInterface[]
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
     * load tree from root to given path
     * @param string $path
     * @param string $languageCode
     * @param string $webspaceKey
     * @param bool $excludeGhost
     * @param bool $loadGhostContent
     * @return StructureInterface[]
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
     * returns a tree of nodes with the given endpoint
     * @param NodeInterface $node
     * @param string $languageCode
     * @param string $webspaceKey
     * @param bool $excludeGhost
     * @param bool $loadGhostContent
     * @param NodeInterface $childNode
     * @return StructureInterface[]
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
            $structure = $this->loadByNode($child, $languageCode, $webspaceKey, $excludeGhost, $loadGhostContent);
            if ($structure === null) {
                continue;
            }

            $result[] = $structure;
            // search structure for child node
            if ($childNode !== null && $childNode === $child) {
                $childStructure = $structure;
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
     * @param string $sql2 The query, which returns the content
     * @param int $limit Limits the number of returned rows
     * @return QueryInterface
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
     * returns data from given node
     * @param NodeInterface $contentNode
     * @param string $localization
     * @param string $webspaceKey
     * @param bool $excludeGhost True if also a ghost page should be returned, otherwise false
     * @param bool $loadGhostContent True if also ghost content should be returned, otherwise false
     * @return StructureInterface
     */
    private function loadByNode(
        NodeInterface $contentNode,
        $localization,
        $webspaceKey,
        $excludeGhost = true,
        $loadGhostContent = false
    ) {
        if ($this->stopwatch) {
            $this->stopwatch->start('contentManager.loadByNode');
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

        if ($excludeGhost && $availableLocalization != $localization) {
            return null;
        }

        // create translated properties
        $this->properties->setLanguage($availableLocalization);

        $templateKey = $contentNode->getPropertyValueWithDefault(
            $this->properties->getName('template'),
            $this->defaultTemplate
        );

        $structure = $this->getStructure($templateKey);

        // set structure to ghost, if the available localization does not match the requested one
        if ($availableLocalization != $localization) {
            $structure->setType(StructureType::getGhost($availableLocalization));
        }

        $structure->setHasTranslation($contentNode->hasProperty($this->properties->getName('template')));

        $structure->setUuid($contentNode->getPropertyValue('jcr:uuid'));
        $structure->setPath(str_replace($this->getContentNode($webspaceKey)->getPath(), '', $contentNode->getPath()));
        $structure->setNodeType($contentNode->getPropertyValueWithDefault($this->properties->getName('nodeType'), Structure::NODE_TYPE_CONTENT));
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
        $structure->setNavigation(
            $contentNode->getPropertyValueWithDefault($this->properties->getName('navigation'), false)
        );
        $structure->setGlobalState(
            $this->getInheritedState($contentNode, $this->properties->getName('state'), $webspaceKey)
        );
        $structure->setPublished(
            $contentNode->getPropertyValueWithDefault($this->properties->getName('published'), null)
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
        foreach ($structure->getExtensions() as $extension) {
            $extension->setLanguageCode($localization, $this->languageNamespace, $this->internalPrefix);
            $extension->load($contentNode, $webspaceKey, $availableLocalization);
        }

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
            $this->stopwatch->stop('contentManager.loadByNode');
        }

        return $structure;
    }

    /**
     * loads dependencies for internal links
     * @param StructureInterface $content
     * @param string $localization
     * @param string $webspaceKey
     * @param bool $loadGhostContent
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
                $content->setInternalLinkContent(
                    $this->load(
                        $internal,
                        $webspaceKey,
                        $localization,
                        $loadGhostContent
                    )
                );
            }
        }
    }

    /**
     * load breadcrumb for given uuid in given language
     * @param $uuid
     * @param $languageCode
     * @param $webspaceKey
     * @return BreadcrumbItemInterface[]
     */
    public function loadBreadcrumb($uuid, $languageCode, $webspaceKey)
    {
        $this->properties->setLanguage($languageCode);

        $sql = 'SELECT *
                FROM  [sulu:content] as parent INNER JOIN [sulu:content] as child
                    ON ISDESCENDANTNODE(child, parent)
                WHERE child.[jcr:uuid]="' . $uuid . '"';

        $query = $this->createSql2Query($sql);
        $nodes = $query->execute();

        $result = array();
        $groundDepth = $this->getContentNode($webspaceKey)->getDepth();

        /** @var NodeInterface $node */
        foreach ($nodes->getNodes() as $node) {
            // uuid
            $nodeUuid = $node->getIdentifier();
            // depth
            $depth = $node->getDepth() - $groundDepth;
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

            $result[] = new BreadcrumbItem($depth, $nodeUuid, $nodeName);
        }

        return $result;
    }

    /**
     * deletes content with subcontent in given webspace
     * @param string $uuid UUID of content
     * @param string $webspaceKey Key of webspace
     */
    public function delete($uuid, $webspaceKey)
    {
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        $this->deleteRecursively($contentNode);
        $session->save();
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
     * calculates publich state of node
     */
    private function getInheritedState(NodeInterface $contentNode, $statePropertyName, $webspaceKey)
    {
        // index page is default PUBLISHED
        $contentRootNode = $this->getContentNode($webspaceKey);
        if ($contentNode->getName() === $contentRootNode->getPath()) {
            return StructureInterface::STATE_PUBLISHED;
        }

        // if test then return it
        if ($contentNode->getPropertyValueWithDefault(
                $statePropertyName,
                StructureInterface::STATE_TEST
            ) === StructureInterface::STATE_TEST
        ) {
            return StructureInterface::STATE_TEST;
        }

        $session = $this->getSession();
        $workspace = $session->getWorkspace();
        $queryManager = $workspace->getQueryManager();

        $sql = 'SELECT *
                FROM  [sulu:content] as parent INNER JOIN [sulu:content] as child
                    ON ISDESCENDANTNODE(child, parent)
                WHERE child.[jcr:uuid]="' . $contentNode->getIdentifier() . '"';

        $query = $queryManager->createQuery($sql, 'JCR-SQL2');
        $result = $query->execute();

        /** @var \PHPCR\NodeInterface $node */
        foreach ($result->getNodes() as $node) {
            // exclude /cmf/sulu_io/contents
            if (
                $node->getPath() !== $contentRootNode->getPath() &&
                $node->getPropertyValueWithDefault(
                    $statePropertyName,
                    StructureInterface::STATE_TEST
                ) === StructureInterface::STATE_TEST
            ) {
                return StructureInterface::STATE_TEST;
            }
        }

        return StructureInterface::STATE_PUBLISHED;
    }
}
