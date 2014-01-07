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
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\ContactBundle\Controller\ContactsController;
use Sulu\Bundle\ContentBundle\Controller\Repository\NodeRepositoryInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Rest\RestController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class NodeController extends RestController implements ClassResourceInterface
{
    /**
     * for returning self link in get action
     * @var string
     */
    private $apiPath = '/admin/api/nodes';

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

        try {
            $result = $this->getRepository()->getNode($uuid, $portal, $language);
            $view = $this->view($result);
        } catch (ItemNotFoundException $ex) {
            $view = $this->view($ex->getMessage(), 404);
        }

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
        if ($flat === 'true') {
            $flat = true;
        } else {
            $flat = false;
        }

        $result = $this->getRepository()->getNodes($parentUuid, $portal, $language, $depth, $flat);

        return $this->handleView(
            $this->view($result)
        );
    }

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

    public function cputIndexAction()
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');
        $template = $this->getRequest()->get('template', 'overview');
        $data = $this->getRequest()->request->all();

        $result = $this->getRepository()->saveIndexNode($data, $template, $portal, $language);

        return $this->handleView($this->view($result));
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

    public function deleteAction($uuid)
    {
        // TODO language
        // TODO portal
        $language = $this->getRequest()->get('language', 'en');
        $portal = $this->getRequest()->get('portal', 'default');

        try {
            $this->getRepository()->deleteNode($uuid, $portal, $language);

            $view = $this->view(null, 204);
        } catch (ItemNotFoundException $ex) {
            $view = $this->view($ex->getMessage(), 404);
        }

        return $this->handleView(
            $view
        );
    }

    /**
     * @return NodeRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('sulu_content.node_repository');
    }
}
