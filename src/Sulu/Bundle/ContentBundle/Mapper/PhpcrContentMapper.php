<?php
/**
 * Created by IntelliJ IDEA.
 * User: danielrotter
 * Date: 14.10.13
 * Time: 11:27
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\ContentBundle\Mapper;


class PhpcrContentMapper extends ContentMapper
{
    public function save($data)
    {
        $template = $this->readTemplate(''); //TODO Set correct file
        $session = $this->getSession(); //TODO Get session in a better way
        $root = $session->getRootNode();
        $node = $root->addNode('cmf/contents/' . $data['title']); //TODO check better way to generate title

        // go through every property in the template
        foreach ($template['properties'] as $property) {
            $type = $this->getType($property['type'], null);

            if (isset($type['phpcr-type'])) {
                // save the simple content types as properties
                $node->setProperty($property['name'], $data[$property['name']]);
            } else {
                // save the data using a complex content type
            }
        }

        $session->save();
    }

    public function saveOld($data)
    {
        $session = $this->getSession();
        $root = $session->getRootNode();

        $parentPath = 'cmf/contents';
        if (isset($data['parent'])) {
            $parentPath .= $data['parent'];
        }

        if ($root->hasNode($parentPath)) {
            $parentNode = $root->getNode($parentPath);

            // Add content
            $contentNode = $parentNode->addNode($data['title']);
            $contentNode->addMixin('mix:referenceable');
            $contentNode->setProperty('title', $data['title']);
            $contentNode->setProperty('article', $data['article']);
            
            $session->save();

            // Add routes
            $routePath = 'cmf/routes' . $data['url'];
            $routePath = explode('/', $routePath);
            $routeNode = $session->getRootNode();

            foreach ($routePath as $path) {
                if ($path != '') {
                    if ($routeNode->hasNode($path)) {
                        $routeNode = $routeNode->getNode($path);
                    } else {
                        $routeNode = $routeNode->addNode($path, 'nt:unstructured');
                    }
                }
            }

            $routeNode->setProperty('content', $contentNode);

            $session->save();
        }
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
