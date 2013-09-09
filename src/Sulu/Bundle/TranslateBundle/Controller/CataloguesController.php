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

use Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException;
use Sulu\Bundle\CoreBundle\Controller\RestController;

/**
 * Make the catalogues available through a REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class CataloguesController extends RestController
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
                ->getRepository('SuluTranslateBundle:Catalogue')
                ->find($id);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Return all catalogues
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCataloguesAction()
    {
        // Already in use - change calls
        $view = $this->responseList();

        return $this->handleView($view);
    }

    /*
     * Returns a list of catalogues from a specific package
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listCataloguesAction()
    {
        $where = array();
        $packageId = $this->getRequest()->get('packageId');
        if (!empty($packageId)) {
            $where = array('package_id' => $packageId);
        }
        $view = $this->responseList($where);

        return $this->handleView($view);
    }

    /**
     * Deletes the catalogue with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteCatalogueAction($id)
    {
        $delete = function ($id) {
            $entityName = 'SuluTranslateBundle:Catalogue';
            $catalogue = $this->getDoctrine()
                ->getRepository($entityName)
                ->find($id);

            if (!$catalogue) {
                throw new EntityNotFoundException($entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($catalogue);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }
}
