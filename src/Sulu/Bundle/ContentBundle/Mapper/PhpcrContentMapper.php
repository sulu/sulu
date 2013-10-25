<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Mapper;

/**
 * Maps the content to PHPCR
 * @package Sulu\Bundle\ContentBundle\Mapper
 */
class PhpcrContentMapper extends ContentMapper
{
    /**
     * Saves the given data from a template to PHPCR
     * @param $data mixed The data to be saved
     */
    public function save($data)
    {
        $template = $this->readTemplate(''); //TODO Set correct file
        $session = $this->getSession(); //TODO Get session in a better way
        $root = $session->getRootNode();
        $node = $root->addNode('cmf/contents/' . $data['title']); //TODO check better way to generate title
        $node->addMixin('mix:referenceable');

        // go through every property in the template
        foreach ($template['properties'] as $property) {
            $type = $this->getType($property['type'], null);

            if (isset($type['phpcr-type'])) {
                // save the simple content types as properties
                $node->setProperty($property['name'], $data[$property['name']]);
            }
        }

        //TODO Implement this save in a more performant way
        $session->save();

        foreach ($template['properties'] as $property) {
            $type = $this->getType($property['type'], null);

            if (!isset($type['phpcr-type'])) {
                // save the data using a complex content type
                $contentTypeClass = 'Sulu\\Bundle\\ContentBundle\\ContentType\\' . ucfirst($type['name']); //TODO make path extendable (service?)
                $contentType = new $contentTypeClass();
                $contentType->save($node, $data[$property['name']]);
            }
        }

        $session->save();
    }

    /**
     * Reads the data from the given path
     * @param $path
     * @return mixed
     */
    public function read($path)
    {
        $session = $this->getSession(); //TODO get session in a better way
        $contentPath ='/cmf/contents' . $path;

        $contentNode = $session->getNode($contentPath);
    }

    /**
     * @return \Jackalope\Session
     */
    protected function getSession()
    {
        $factoryclass = '\Jackalope\RepositoryFactoryJackrabbit';
        $parameters = array('jackalope.jackrabbit_uri' => 'http://localhost:8080/server');
        $factory = new $factoryclass();
        $repository = $factory->getRepository($parameters);
        $credentials = new \PHPCR\SimpleCredentials('admin', 'admin');
        return $repository->login($credentials, 'default');
    }
}
