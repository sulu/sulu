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

use PHPCR\SessionInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class ContentMapper extends ContainerAware implements ContentMapperInterface
{

    private $basePath = '/cmf/contents';

    /**
     * Saves the given data in the content storage
     * @param $data array The data to be saved
     * @param $language string Save data for given language
     * @param $templateKey string name of template
     * @return StructureInterface
     */
    public function save($data, $language, $templateKey = '')
    {
        // TODO localice
        $structure = $this->getStructure($templateKey); //TODO Set correct file
        $session = $this->getSession();
        $root = $session->getRootNode();
        $node = $root->addNode(
            ltrim($this->getBasePath(), '/') . '/' . $data['title']
        ); //TODO check better way to generate title, tree?
        $node->addMixin('mix:referenceable');

        $postSave = array();

        // go through every property in the template
        /** @var PropertyInterface $property */
        foreach ($structure->getProperties() as $property) {
            if (isset($data[$property->getName()])) {
                $type = $this->getContentType($property->getContentTypeName());
                $value = $data[$property->getName()];
                if ($type->getType() == ContentTypeInterface::POST_SAVE) {
                    $postSave[] = array(
                        'type' => $type,
                        'property' => $property,
                        'value' => $value
                    );
                } else {
                    $property->setValue($value);
                    $type->set($node, $property, $value);
                }
            }
        }

        $session->save();

        foreach ($postSave as $post) {
            $post['property']->setValue($post['value']);
            $post['type']->set($node, $post['property']);
        }

        $session->save();
    }

    /**
     * Reads the data from the given path
     * @param $path string path to the content
     * @param $language string read data for given language
     * @param $templateKey string name of template
     * @return StructureInterface
     */
    public function read($path, $language, $templateKey = '')
    {
        // TODO localice
        $structure = $this->getStructure($templateKey); //TODO Set correct file
        $session = $this->getSession();
        $contentPath = $this->getBasePath() . $path;

        $contentNode = $session->getNode($contentPath);

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

    protected function getBasePath()
    {
        return $this->basePath;
    }
}
