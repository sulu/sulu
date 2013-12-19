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
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;

class ContentMapper extends ContainerAware implements ContentMapperInterface
{
    /**
     * base path to save the content
     * @var string
     */
    private $contentBasePath = '/cmf/contents';

    /**
     * base path to load the route
     * @var string
     */
    private $routesBasePath = '/cmf/routes';

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

    public function __construct($contentBasePath, $routesBasePath)
    {
        $this->contentBasePath = $contentBasePath;
        $this->routesBasePath = $routesBasePath;
    }

    /**
     * saves the given data in the content storage
     * @param array $data The data to be saved
     * @param string $templateKey Name of template
     * @param string $portalKey Key of portal
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
        $portalKey,
        $languageCode,
        $userId,
        $partialUpdate = true,
        $uuid = null,
        $parentUuid = null
    )
    {
        // TODO localize
        // TODO portal
        $structure = $this->getStructure($templateKey);
        $session = $this->getSession();

        if ($parentUuid !== null) {
            $root = $session->getNodeByIdentifier($parentUuid);
        } else {
            $root = $session->getNode($this->getContentBasePath());
        }

        $path = $this->cleanUp($data['title']);

        $dateTime = new \DateTime();

        /** @var NodeInterface $node */
        if ($uuid === null) {
            // create a new node
            $node = $root->addNode($path);
            $node->setProperty('sulu:creator', $userId);
            $node->setProperty('sulu:created', $dateTime);

            $node->addMixin('sulu:content');
        } else {
            $node = $session->getNodeByIdentifier($uuid);

            if (
                $node->getPath() !== $this->getContentBasePath() &&
                (!$node->hasProperty('title') || $node->getPropertyValue('title') !== $data['title'])
            ) {
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
                    $type->set($node, $property);
                }
            } elseif (!$partialUpdate) {
                $type = $this->getContentType($property->getContentTypeName());
                // if it is not a partial update remove property
                $type->remove($node, $property);
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

                $type->set($node, $property);
            } catch (Exception $ex) { // TODO Introduce a PostSaveException, so that we don't have to catch everything
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
     * @param string $portalKey Key of portal
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
        $portalKey,
        $languageCode,
        $userId,
        $partialUpdate = true
    )
    {
        $uuid = $this->getSession()->getNode($this->getContentBasePath())->getIdentifier();
        return $this->save($data, $templateKey, $portalKey, $languageCode, $userId, $partialUpdate, $uuid);
    }

    /**
     * returns a list of data from children of given node
     * @param $uuid
     * @param $portalKey
     * @param $languageCode
     *
     * @return StructureInterface[]
     */
    public function loadByParent($uuid, $portalKey, $languageCode)
    {
        // TODO portal
        if ($uuid != null) {
            $root = $this->getSession()->getNodeByIdentifier($uuid);
        } else {
            $root = $this->getSession()->getNode($this->getContentBasePath());
        }
        $result = array();

        foreach ($root->getNodes() as $node) {
            $result[] = $this->loadByNode($node, $languageCode);
        }

        return $result;
    }

    /**
     * returns the data from the given id
     * @param string $uuid UUID of the content
     * @param string $portalKey Key of portal
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function load($uuid, $portalKey, $languageCode)
    {
        // TODO portal
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByNode($contentNode, $languageCode);
    }

    /**
     * returns the data from the given id
     * @param string $portalKey Key of portal
     * @param string $languageCode Read data for given language
     * @return StructureInterface
     */
    public function loadStartPage($portalKey, $languageCode)
    {
        // TODO Portal
        $uuid = $this->getSession()->getNode($this->getContentBasePath())->getIdentifier();

        return $this->load($uuid, $portalKey, $languageCode);
    }

    /**
     * returns data from given path
     * @param string $resourceLocator Resource locator
     * @param string $portalKey Key of portal
     * @param string $languageCode
     * @return StructureInterface
     */
    public function loadByResourceLocator($resourceLocator, $portalKey, $languageCode)
    {
        // TODO portal
        $session = $this->getSession();
        $uuid = $this->getResourceLocator()->loadContentNodeUuid($resourceLocator);
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByNode($contentNode, $languageCode);
    }

    /**
     * returns data from given node
     * @param NodeInterface $contentNode
     * @param string $language
     * @return StructureInterface
     */
    private function loadByNode(NodeInterface $contentNode, $language)
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
            $type->get($contentNode, $property);
        }

        return $structure;
    }

    /**
     * deletes content with subcontent in given portal
     * @param string $uuid UUID of content
     * @param string $portalKey Key of portal
     */
    public function delete($uuid, $portalKey)
    {
        // TODO portal
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        $contentNode->remove();
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
     * @return SessionInterface
     */
    protected function getSession()
    {
        return $this->container->get('sulu.phpcr.session')->getSession();
    }

    /**
     * @return string
     */
    protected function getContentBasePath()
    {
        return $this->contentBasePath;
    }

    /**
     * @return string
     */
    protected function getRouteBasePath()
    {
        return $this->routesBasePath;
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
