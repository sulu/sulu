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
 * Makes the translation codes accessible trough an REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class CodesController extends FOSRestController
{
    private $entityName = 'SuluTranslateBundle:Code';

    /**
     * Lists all the codes or filters the codes by parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCodesAction()
    {
        $listHelper = $this->get('sulu_core.list_rest_helper');
        $fields = $listHelper->getFields();
        $limit = $listHelper->getLimit();
        $offset = $listHelper->getOffset();
        $sorting = $listHelper->getSorting();

        $where = array();
        if ($this->getRequest()->get('packageId') != null) {
            $where['package_id'] = $this->getRequest()->get('packageId');
        }
        if ($this->getRequest()->get('packageId') != null) {
            $where['translations_catalogue_id'] = $this->getRequest()->get('catalogueId');
        }

        $codes = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findFiltered($fields, $limit, $offset, $sorting, $where);

        $response = array(
            'total' => sizeof($codes),
            'items' => $codes
        );
        $view = $this->view($response, 200);
        return $this->handleView($view);
    }
}
