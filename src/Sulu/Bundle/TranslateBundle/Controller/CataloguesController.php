<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
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

        $view = $this->view($catalogues, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the catalogue with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCatalogueAction($id)
    {
        $catalogue = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Catalogue')
            ->find($id);

        $view = $this->view($catalogue, 200);

        return $this->handleView($view);
    }
}