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
use PHPCR\ItemNotFoundException;
use Sulu\Bundle\ContentBundle\Controller\Repository\NodeRepositoryInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;

class NodeController extends RestController implements ClassResourceInterface
{

    /**
     * returns a content item with given UUID as JSON String
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($uuid)
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');

        $view = $this->responseGetById(
            $uuid,
            function ($id) use ($language, $portal) {
                try {
                    return $this->getRepository()->getNode($id, $portal, $language);
                } catch (ItemNotFoundException $ex) {
                    return null;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * returns a content item for startpage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');

        $result = $this->getRepository()->getIndexNode($portal, $language);

        return $this->handleView($this->view($result));
    }

    /**
     * returns all content items as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        // TODO pagination
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');
        $parentUuid = $this->getRequest()->get('parent');
        $depth = $this->getRequest()->get('depth', 1);
        $depth = intval($depth);
        $flat = $this->getRequest()->get('flat', 'true');
        $flat = ($flat === 'true');

        $result = $this->getRepository()->getNodes($parentUuid, $portal, $language, $depth, $flat);

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * saves node with given uuid and data
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($uuid)
    {
        // TODO portal
        // TODO language
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');
        $template = $this->getRequest()->get('template');
        $data = $this->getRequest()->request->all();

        $result = $this->getRepository()->saveNode($data, $template, $portal, $language, $uuid);

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * save action for index page /nodes/index
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cputIndexAction()
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');
        $template = $this->getRequest()->get('template', 'overview');
        $data = $this->getRequest()->request->all();

        try {
            if ($data['url'] != '/') {
                throw new InvalidArgumentException('Content', 'url', 'url of index page can not be changed');
            }

            $result = $this->getRepository()->saveIndexNode($data, $template, $portal, $language);
            $view = $this->view($result);
        } catch (RestException $ex) {
            $view = $this->view(
                $ex->toArray(),
                400
            );
        }

        return $this->handleView($view);
    }

    /**
     * Updates a content item and returns result as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');
        $template = $this->getRequest()->get('template', 'overview');
        $data = $this->getRequest()->request->all();
        $parent = $this->getRequest()->get('parent');

        $result = $this->getRepository()->saveNode($data, $template, $portal, $language, null, $parent);

        return $this->handleView(
            $this->view($result)
        );
    }

    /**
     * deletes node with given uuid
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($uuid)
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');

        $view = $this->responseDelete(
            $uuid,
            function ($id) use ($language, $portal) {
                try {
                    $this->getRepository()->deleteNode($id, $portal, $language);
                } catch (ItemNotFoundException $ex) {
                    throw new EntityNotFoundException('Content', $id);
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * @return NodeRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('sulu_content.node_repository');
    }
}
