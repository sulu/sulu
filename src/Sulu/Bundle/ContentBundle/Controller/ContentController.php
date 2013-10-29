<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Rest\RestController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContentController extends RestController implements ClassResourceInterface
{
    public function getAction($path)
    {
        $view = $this->responseGetById(
            $path,
            function ($path) {
                // TODO language
                return $this->getMapper()->read($path, 'en');
            }
        );

        return $this->handleView($view);
    }

    public function cgetAction()
    {
        $result = array();
        $basePath = '/cmf/contents';

        // FIXME make it better
        $session = $this->getSession();
        $root = $session->getRootNode();
        $contents = $root->getNode($basePath);

        /** @var NodeInterface $node */
        foreach ($contents as $node) {
            $result[] = $this->getMapper()->read(str_replace('/cmf/contents', '', $node->getPath()), 'en');
        }

        return $this->handleView(
            $this->view(
                array(
                    'total' => sizeof($result),
                    'items' => $result
                )
            )
        );
    }

    public function postAction()
    {
        // TODO language
        $key = $this->getRequest()->get('template');
        $structure = $this->getMapper()->save($this->getRequest()->query->all(), 'en', $key);
        $view = $this->view(200, json_encode($structure));

        return $this->handleView($view);
    }

    /**
     * @return ContentMapperInterface
     */
    protected function getMapper()
    {
        return $this->container->get('sulu.content.mapper');
    }

    /**
     * @return SessionInterface
     */
    protected function getSession()
    {
        return $this->container->get('sulu.phpcr.session')->getSession;
    }
}
