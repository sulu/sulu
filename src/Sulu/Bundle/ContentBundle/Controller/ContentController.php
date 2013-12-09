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
use Sulu\Bundle\ContactBundle\Controller\ContactsController;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Rest\RestController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContentController extends RestController implements ClassResourceInterface
{
    /**
     * for returning self link in get action
     * @var string
     */
    private $apiPath = '/admin/api/contents';

    /**
     * returns a content item with given UUID as JSON String
     * @param $uuid
     * @return \Symfony\Component\HttpFoundation\Response
     */

    public function getAction($uuid)
    {
        $view = $this->responseGetById(
            $uuid,
            function ($uuid) {
                // TODO language
                // TODO portal
                $content = $this->getMapper()->load($uuid, 'default', 'en');
                $result = $content->toArray();
                $result['_links'] = array('self' => $this->apiPath . '/' . $uuid);
                $result['creator'] = $this->getContactByUserId($result['creator']);
                $result['changer'] = $this->getContactByUserId($result['changer']);

                return $result;
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
            // TODO portal
            $tmp = $this->getMapper()->load($node->getIdentifier(), 'default', 'en')->toArray();
            $tmp['creator'] = $this->getContactByUserId($tmp['creator']);
            $tmp['changer'] = $this->getContactByUserId($tmp['changer']);
            $result[] = $tmp;
        }

        return $this->handleView(
            $this->view(
                array(
                    '_links' => array('self' => $this->getRequest()->getUri()),
                    '_embedded' => $result,
                    'total' => sizeof($result),
                )
            )
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

        $view = $this->view($result, 200);

        return $this->handleView($view);
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
        $userId = $this->get('security.context')->getToken()->getUser()->getId();
        // TODO portal
        $structure = $this->getMapper()->save(
            $this->getRequest()->request->all(),
            $templateKey,
            'default',
            'en',
            $userId
        );
        $result = $structure->toArray();
        $result['creator'] = $this->getContactByUserId($result['creator']);
        $result['changer'] = $this->getContactByUserId($result['changer']);
        $view = $this->view($result, 200);

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
    protected function getBasePath()
    {
        return $this->container->getParameter('sulu.content.base_path.content');
    }
}
