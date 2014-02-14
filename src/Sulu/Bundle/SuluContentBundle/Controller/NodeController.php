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
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;
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
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        $view = $this->responseGetById(
            $uuid,
            function ($id) use ($language, $webspace) {
                try {
                    return $this->getRepository()->getNode($id, $webspace, $language);
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
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        $result = $this->getRepository()->getIndexNode($webspace, $language);

        return $this->handleView($this->view($result));
    }

    /**
     * returns all content items as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        $parentUuid = $this->getRequest()->get('parent');
        $depth = $this->getRequest()->get('depth', 1);
        $depth = intval($depth);
        $flat = $this->getRequest()->get('flat', 'true');
        $flat = ($flat === 'true');

        // TODO pagination
        $result = $this->getRepository()->getNodes($parentUuid, $webspace, $language, $depth, $flat);

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
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');
        $template = $this->getRequest()->get('template');
        $state = $this->getRequest()->get('state');
        if ($state !== null) {
            $state = intval($state);
        }
        $data = $this->getRequest()->request->all();

        $result = $this->getRepository()->saveNode($data, $template, $webspace, $language, $uuid, null, $state);

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
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');
        $template = $this->getRequest()->get('template', 'overview');
        $data = $this->getRequest()->request->all();

        try {
            if ($data['url'] != '/') {
                throw new InvalidArgumentException('Content', 'url', 'url of index page can not be changed');
            }

            $result = $this->getRepository()->saveIndexNode($data, $template, $webspace, $language);
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
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');
        $template = $this->getRequest()->get('template', 'overview');
        $parent = $this->getRequest()->get('parent');
        $data = $this->getRequest()->request->all();

        $result = $this->getRepository()->saveNode($data, $template, $webspace, $language, null, $parent);

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
        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        $view = $this->responseDelete(
            $uuid,
            function ($id) use ($language, $webspace) {
                try {
                    $this->getRepository()->deleteNode($id, $webspace, $language);
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
