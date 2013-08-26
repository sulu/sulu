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

use FOS\RestBundle\Controller\FOSRestController;

/**
 * Make the catalogues available through a REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class CataloguesController extends FOSRestController
{
    public function getCataloguesAction()
    {
        $response = array();

        $packageId = $this->getRequest()->get('package');

        $repository = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Catalogue');

        if ($packageId != null) {
            $catalogues = $repository->findBy(
                array(
                    'package' => $packageId
                )
            );
        } else {
            $catalogues = $repository->findAll();
        }

        $response['total'] = count($catalogues);
        $response['items'] = $catalogues;

        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the catalogue with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCatalogueAction($id)
    {
        $response = array();

        $catalogue = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Catalogue')
            ->find($id);

        $view = $this->view($catalogue, 200);

        return $this->handleView($view);
    }

    public function deleteCatalogueAction($id) {

        $response = array();

        $catalogue = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Catalogue')
            ->find($id);

        if($catalogue != null) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($catalogue);
            $em->flush();

            $view = $this->view(null, 204);

        } else {
            $view = $this->view(null, 404);

        }

        return $this->handleView($view);
    }
}
