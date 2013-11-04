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
    /**
     * returns a content item with given UUID as JSON String
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function ($id) {
                // TODO language
                $content = $this->getMapper()->read($id, 'en');

                return $content->toArray();
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns all content items as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        // TODO uuid of parent?
        $result = array();
        $basePath = $this->getBasePath();

        // FIXME make it better
        $session = $this->getSession();
        $contents = $session->getNode($basePath);

        /** @var NodeInterface $node */
        foreach ($contents as $node) {
            // TODO language
            $result[] = $this->getMapper()->read($node->getPropertyValue('jcr:uuid'), 'en')->toArray();
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

    /**
     * Updates a content item and returns result as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        // TODO language
        $key = $this->getRequest()->get('template');
        $structure = $this->getMapper()->save($this->getRequest()->request->all(), 'en', $key);
        $view = $this->view($structure->toArray(), 200);

        return $this->handleView($view);
    }

    /**
     * return content mapper
     * @return ContentMapperInterface
     */
    protected function getMapper()
    {
        return $this->container->get('sulu.content.mapper');
    }

    /**
     * returns phpcr session
     * @return SessionInterface
     */
    protected function getSession()
    {
        return $this->container->get('sulu.phpcr.session')->getSession();
    }

    /**
     * return base content path
     * @return string
     */
    protected function getBasePath(){
        return $this->container->getParameter('sulu.content.base_path.content');
    }
}
