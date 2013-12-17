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
        // TODO pagination
        $result = array();

        $parentUuid = $this->getRequest()->get('parent');
        $depth = $this->getRequest()->get('depth');

        // TODO handle different depths
        if ($depth == 1) {
            // TODO language, portal
            $structures = $this->getMapper()->loadByParent($parentUuid, 'default', 'de');
            foreach ($structures as $structure) {
                $tmp = $structure->toArray();
                $tmp['creator'] = $this->getContactByUserId($tmp['creator']);
                $tmp['changer'] = $this->getContactByUserId($tmp['changer']);

                // TODO published, linked, type
                $tmp['published'] = true;
                $tmp['linked'] = false;
                $tmp['type'] = 'none';

                // hal spec
                $tmp['_links'] = array(
                    'self' => $this->apiPath . '/' . $tmp['id'],
                    'children' => $this->apiPath . '?parent=' . $tmp['id'] . '&depth=' . $depth
                );
                $tmp['_embedded'] = array();

                $result[] = $tmp;
            }
        }

        if ($parentUuid !== null) {
            $parent = $this->getMapper()->load($parentUuid, 'default', 'de')->toArray();
            $result = array_merge(
                $parent,
                array(
                    '_links' => array(
                        'self' => $this->apiPath . '/' . $parent['id'],
                        'children' => $this->apiPath . '?parent=' . $parent['id'] . '&depth=' . $depth
                    ),
                    '_embedded' => $result,
                    'total' => sizeof($result),
                )
            );
        } else {
            $result = array(
                '_links' => array(
                    'children' => $this->apiPath . '?depth=' . $depth
                ),
                '_embedded' => $result,
                'total' => sizeof($result)
            );
        }

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
