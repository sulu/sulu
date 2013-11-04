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

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Util\UuidUtils;
use Symfony\Component\DependencyInjection\ContainerAware;

class ContentMapper extends ContainerAware implements ContentMapperInterface
{
    /**
     * base path to save the content
     * @var string
     */
    private $basePath = '/cmf/contents';

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Saves the given data in the content storage
     * @param $data array The data to be saved
     * @param $language string Save data for given language
     * @param $templateKey string name of template
     * @return StructureInterface
     */
    public function save($data, $language, $templateKey)
    {
        // TODO localize
        $structure = $this->getStructure($templateKey);
        $session = $this->getSession();
        $root = $session->getRootNode();
        /** @var NodeInterface $node */
        $node = $root->addNode(
            ltrim($this->getBasePath(), '/') . '/' . $data['title']
        ); //TODO check better way to generate title, tree?
        $node->addMixin('mix:referenceable');
        $node->setProperty('template', $templateKey); // TODO namespace ??? sulu:template

        // if is new node
        if($node->getIdentifier() == null || is_empty($node->getIdentifier())){
            /** @var \Sulu\Bundle\SecurityBundle\Entity\User $user */
            $user = $this->container->get('security.context')->getToken()->getUser();
            $node->setProperty('creator',$user->getId());
            $node->setProperty('created',new \DateTime());
        }

        $node->setProperty('changed',new \DateTime());

        $postSave = array();

        $structure->setId($node->getIndex());

        // go through every property in the template
        foreach ($structure->getProperties() as $property) {
            /** @var PropertyInterface $property */
            if (isset($data[$property->getName()])) {
                $type = $this->getContentType($property->getContentTypeName());
                $value = $data[$property->getName()];
                $property->setValue($value);
                if ($type->getType() == ContentTypeInterface::POST_SAVE) {
                    $postSave[] = array(
                        'type' => $type,
                        'property' => $property
                    );
                } else {
                    $type->set($node, $property);
                }
            }
        }

        // save node now
        $session->save();

        // set post save content types properties
        foreach ($postSave as $post) {
            /** @var ContentTypeInterface $type */
            $type = $post['type'];
            /** @var PropertyInterface $property */
            $property = $post['property'];
            $type->set($node, $property);
        }

        $session->save();

        $structure->setId($node->getPropertyValue('jcr:uuid'));

        return $structure;
    }

    /**
     * Reads the data from the given path
     * @param $id string uuid to the content
     * @param $language string read data for given language
     * @return StructureInterface
     */
    public function read($id, $language)
    {
        $session = $this->getSession();
        $contentNode = $session->getNodeByIdentifier($id);

        $templateKey = $contentNode->getPropertyValue('template'); // TODO namespace ??? sulu:template

        // TODO localize
        $structure = $this->getStructure($templateKey);

        $structure->setId($contentNode->getPropertyValue('jcr:uuid'));

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties() as $property) {
            $type = $this->getContentType($property->getContentTypeName());
            $type->get($contentNode, $property);
        }

        return $structure;
    }

    /**
     * returns a structure with given key
     * @param $key
     * @return StructureInterface
     */
    protected function getStructure($key)
    {
        return $this->container->get('sulu.content.structure_manager')->getStructure($key);
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
    protected function getBasePath()
    {
        return $this->basePath;
    }
}
