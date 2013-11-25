<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\RestController;

/**
 * Make the catalogues available through a REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class CatalogueController extends RestController
{
    protected $entityName = 'SuluTranslateBundle:Catalogue';

    /**
     * Returns the catalogue with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCatalogueAction($id)
    {
        $find = function ($id) {
            return $this->getDoctrine()
                ->getRepository($this->entityName)
                ->getCatalogueById($id);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }


    /**
     * Returns a list of catalogues (from a specific package)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetCataloguesAction()
    {
        if ($this->getRequest()->get('flat')=='true') {
            // flat structure
            $where = array();
            $packageId = $this->getRequest()->get('packageId');
            if (!empty($packageId)) {
                $where = array('package_id' => $packageId);
            }
            $view = $this->responseList($where);
        } else {
            $entities = $this->getDoctrine()->getRepository($this->entityName)->findAll();
            $view = $this->view($this->createHalResponse($entities), 200);
        }
        return $this->handleView($view);
    }


    /**
     * Deletes the catalogue with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteCataloguesAction($id)
    {
        $delete = function ($id) {
            $catalogue = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->getCatalogueById($id);

            if (!$catalogue) {
                throw new EntityNotFoundException($this->entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($catalogue);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }
}
