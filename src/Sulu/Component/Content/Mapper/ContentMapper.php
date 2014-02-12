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

use PHPCR\ItemExistsException;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;

class ContentMapper extends ContainerAware implements ContentMapperInterface
{

    /**
     * namespace of translation
     * @var string
     */
    private $languageNamespace;

    /**
     * default language of translation
     * @var string
     */
    private $defaultLanguage;

    /**
     * TODO abstract with cleanup from RLPStrategy
     * replacers for cleanup
     * @var array
     */
    protected $replacers = array(
        'default' => array(
            ' ' => '-',
            '+' => '-',
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            // because strtolower ignores Ä,Ö,Ü
            'Ä' => 'ae',
            'Ö' => 'oe',
            'Ü' => 'ue'
            // TODO should be filled
        ),
        'de' => array(
            '&' => 'und'
        ),
        'en' => array(
            '&' => 'and'
        )
    );

    public function __construct($defaultLanguage, $languageNamespace)
    {
        $this->defaultLanguage = $defaultLanguage;
        $this->languageNamespace = $languageNamespace;
    }

    /**
     * saves the given data in the content storage
     * @param array $data The data to be saved
     * @param string $templateKey Name of template
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Save data for given language
     * @param int $userId The id of the user who saves
     * @param bool $partialUpdate ignore missing property
     * @param string $parentUuid uuid of parent node
     * @param string $uuid uuid of node if exists
     *
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
        $parentUuid = null
    )
    {
        // TODO localize
        $structure = $this->getStructure($templateKey);
        $session = $this->getSession();

        if ($parentUuid !== null) {
            $root = $session->getNodeByIdentifier($parentUuid);
        } else {
            $root = $this->getContentNode($webspaceKey);
        }

        $path = $this->cleanUp($data['title']);

        $dateTime = new \DateTime();

        $titleProperty = new TranslatedProperty($structure->getProperty(
            'title'
        ), $languageCode, $this->languageNamespace);

        /** @var NodeInterface $node */
        if ($uuid === null) {
            // create a new node
            $node = $root->addNode($path);
            $node->setProperty('sulu:creator', $userId);
            $node->setProperty('sulu:created', $dateTime);

            $node->addMixin('sulu:content');
        } else {
            $node = $session->getNodeByIdentifier($uuid);

            $hasSameLanguage = ($languageCode == $this->defaultLanguage);
            $hasSamePath = ($node->getPath() !== $this->getContentNode($webspaceKey)->getPath());
            $hasDifferentTitle = !$node->hasProperty($titleProperty->getName()) ||
                $node->getPropertyValue($titleProperty->getName()) !== $data['title'];

            if ($hasSameLanguage && $hasSamePath && $hasDifferentTitle) {
                $node->rename($path);
                // FIXME refresh session here
            }
        }
        $node->setProperty('sulu:template', $templateKey);

        $node->setProperty('sulu:changer', $userId);
        $node->setProperty('sulu:changed', $dateTime);

        $postSave = array();

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties() as $property) {

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
                    $type->set(
                        $node,
                        new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                        $webspaceKey
                    );
                }
            } elseif (!$partialUpdate) {
                $type = $this->getContentType($property->getContentTypeName());
                // if it is not a partial update remove property
                $type->remove($node, new TranslatedProperty($property, $languageCode, $this->languageNamespace));
            }
            // if it is a partial update ignore property
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

                $type->set(
                    $node,
                    new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                    $webspaceKey
                );
            } catch (Exception $ex) {
                // TODO Introduce a PostSaveException, so that we don't have to catch everything
                // FIXME message for user or log entry
            }
        }

        $session->save();

        $structure->setUuid($node->getPropertyValue('jcr:uuid'));
        $structure->setCreator($node->getPropertyValue('sulu:creator'));
        $structure->setChanger($node->getPropertyValue('sulu:changer'));
        $structure->setCreated($node->getPropertyValue('sulu:created'));
        $structure->setChanged($node->getPropertyValue('sulu:changed'));

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
    )
    {
        $uuid = $this->getContentNode($webspaceKey)->getIdentifier();
        return $this->save($data, $templateKey, $webspaceKey, $languageCode, $userId, $partialUpdate, $uuid);
    }

    /**
     * returns a list of data from children of given node
     * @param $uuid
     * @param $webspaceKey
     * @param $languageCode
     * @param int $depth
     * @param bool $flat
     *
     * @return StructureInterface[]
     */
    public function loadByParent($uuid, $webspaceKey, $languageCode, $depth = 1, $flat = true)
    {
        if ($uuid != null) {
            $root = $this->getSession()->getNodeByIdentifier($uuid);
        } else {
            $root = $this->getContentNode($webspaceKey);
        }
        return $this->loadByParentNode($root, $webspaceKey, $languageCode, $depth, $flat);
    }

    /**
     * returns a list of data from children of given node
     * @param NodeInterface $parent
     * @param $webspaceKey
     * @param $languageCode
     * @param int $depth
     * @param bool $flat
     * @return array
     */
    private function loadByParentNode(NodeInterface $parent, $webspaceKey, $languageCode, $depth = 1, $flat = true)
    {
        $results = array();

        /** @var NodeInterface $node */
        foreach ($parent->getNodes() as $node) {
            $result = $this->loadByNode($node, $languageCode, $webspaceKey);
            $results[] = $result;
            if ($depth > 1) {
                $children = $this->loadByParentNode($node, $webspaceKey, $languageCode, $depth - 1, $flat);
                if ($flat) {
                    $results = array_merge($results, $children);
                } else {
                    $result->setChildren($children);
                }
            }
        }

        return $results;
    }

    /**
     * returns the data from the given id
     * @param string $uuid UUID of the content
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function load($uuid, $webspaceKey, $languageCode)
    {
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByNode($contentNode, $languageCode, $webspaceKey);
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

        return $this->load($uuid, $webspaceKey, $languageCode);
    }

    /**
     * returns data from given path
     * @param string $resourceLocator Resource locator
     * @param string $webspaceKey Key of webspace
     * @param string $languageCode
     * @return StructureInterface
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode)
    {
        $session = $this->getSession();
        $uuid = $this->getResourceLocator()->loadContentNodeUuid($resourceLocator, $webspaceKey);
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

        $queryManager = $this->getSession()->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        if ($limit) {
            $query->setLimit($limit);
        }
        $result = $query->execute();

        foreach ($result->getNodes() as $node) {
            $structures[] = $this->loadByNode($node, $languageCode, $webspaceKey);
        }

        return $structures;
    }

    /**
     * returns data from given node
     * @param NodeInterface $contentNode
     * @param string $languageCode
     * @param string $webspaceKey
     * @return StructureInterface
     */
    private function loadByNode(NodeInterface $contentNode, $languageCode, $webspaceKey)
    {
        $templateKey = $contentNode->getPropertyValue('sulu:template');

        // TODO localize
        $structure = $this->getStructure($templateKey);

        $structure->setUuid($contentNode->getPropertyValue('jcr:uuid'));
        $structure->setCreator($contentNode->getPropertyValue('sulu:creator'));
        $structure->setChanger($contentNode->getPropertyValue('sulu:changer'));
        $structure->setCreated($contentNode->getPropertyValue('sulu:created'));
        $structure->setChanged($contentNode->getPropertyValue('sulu:changed'));
        $structure->setHasChildren($contentNode->hasNodes());

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties() as $property) {
            $type = $this->getContentType($property->getContentTypeName());
            $type->get(
                $contentNode,
                new TranslatedProperty($property, $languageCode, $this->languageNamespace),
                $webspaceKey
            );
        }

        return $structure;
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
        return $this->container->get('sulu.content.structure_manager')->getStructure($key);
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
        return $this->container->get('sulu.content.type.' . $name);
    }

    /**
     * @param $webspaceKey
     * @return NodeInterface
     */
    protected function getContentNode($webspaceKey)
    {
        return $this->container->get('sulu.phpcr.session')->getContentNode($webspaceKey);
    }

    /**
     * @return SessionInterface
     */
    protected function getSession()
    {
        return $this->container->get('sulu.phpcr.session')->getSession();
    }

    /**
     * @param $webspaceKey
     * @return NodeInterface
     */
    protected function getRouteNode($webspaceKey)
    {
        return $this->container->get('sulu.phpcr.session')->getRouteNode($webspaceKey);
    }

    /**
     * TODO abstract with cleanup from RLPStrategy
     * @param string $dirty
     * @return string
     */
    protected function cleanUp($dirty)
    {
        $clean = strtolower($dirty);

        // TODO language
        $languageCode = 'de';
        $replacers = array_merge($this->replacers['default'], $this->replacers[$languageCode]);

        if (count($replacers) > 0) {
            foreach ($replacers as $key => $value) {
                $clean = str_replace($key, $value, $clean);
            }
        }

        // Inspired by ZOOLU
        // delete problematic characters
        $clean = str_replace('%2F', '/', urlencode(preg_replace('/([^A-za-z0-9\s-_\/])/', '', $clean)));

        // replace multiple minus with one
        $clean = preg_replace('/([-]+)/', '-', $clean);

        // delete minus at the beginning or end
        $clean = preg_replace('/^([-])/', '', $clean);
        $clean = preg_replace('/([-])$/', '', $clean);

        // remove double slashes
        $clean = str_replace('//', '/', $clean);

        return $clean;
    }
}
