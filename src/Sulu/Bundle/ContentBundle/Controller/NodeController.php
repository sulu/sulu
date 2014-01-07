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
        $templateKey = $this->getRequest()->get('template');
        $userId = $this->get('security.context')->getToken()->getUser()->getId();

        $structure = $this->getMapper()->save(
            $this->getRequest()->request->all(),
            $templateKey,
            'default',
            'en',
            $userId,
            true,
            $uuid
        );
        $result = $structure->toArray();
        $result['creator'] = $this->getContactByUserId($result['creator']);
        $result['changer'] = $this->getContactByUserId($result['changer']);

        return $this->handleView(
            $this->view(
                array(
                    '_links' => array('self' => $this->getRequest()->getUri()),
                    '_embedded' => array($result),
                    'total' => 1,
                )
            )
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

        $result = $this->get('sulu_content.node_repository')->saveStartNode($data, $template, $portal, $language);

        return $this->handleView($this->view($result));
    }

    private function getContactByUserId($id)
    {
        // Todo performance issue
        // Todo solve as service
        $user = $this->getDoctrine()->getRepository('SuluSecurityBundle:User')->find($id);

        if ($user !== null) {
            $contact = $user->getContact();

            return $contact->getFirstname() . " " . $contact->getLastname();
        } else {
            return "";
        }
    }

    /**
     * Updates a content item and returns result as JSON String
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        // TODO language
        $templateKey = $this->getRequest()->get('template');
        $parentUuid = $this->getRequest()->get('parent');

        $userId = $this->get('security.context')->getToken()->getUser()->getId();

        // TODO portal
        $structure = $this->getMapper()->save(
            $this->getRequest()->request->all(),
            $templateKey,
            'default',
            'en',
            $userId,
            true,
            null,
            $parentUuid
        );
        $result = $structure->toArray();
        $result['creator'] = $this->getContactByUserId($result['creator']);
        $result['changer'] = $this->getContactByUserId($result['changer']);

        return $this->handleView(
            $this->view(
                array(
                    '_links' => array('self' => $this->getRequest()->getUri()),
                    '_embedded' => array($result),
                    'total' => 1,
                )
            )
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
     * return content mapper
     * @return ContentMapperInterface
     */
    protected function getMapper()
    {
        return $this->container->get('sulu.content.mapper');
    }

    /**
     * @return NodeRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('sulu_content.node_repository');
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
    protected function getBasePath()
    {
        return $this->container->getParameter('sulu.content.base_path.content');
    }
}
