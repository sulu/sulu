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
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Translation;

/**
 * Makes the translation codes accessible trough an REST-API
 * @package Sulu\Bundle\TranslateBundle\Controller
 */
class CodesController extends FOSRestController
{
    private $codeEntity = 'SuluTranslateBundle:Code';
    private $catalogueEntity = 'SuluTranslateBundle:Catalogue';
    private $packageEntity = 'SuluTranslateBundle:Package';
    private $locationEntity = 'SuluTranslateBundle:Location';

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
        if ($this->getRequest()->get('catalogueId') != null) {
            $where['translations_catalogue_id'] = $this->getRequest()->get('catalogueId');
        }

        $codes = $this->getDoctrine()
            ->getRepository($this->codeEntity)
            ->findFiltered($fields, $limit, $offset, $sorting, $where);

        $response = array(
            'total' => sizeof($codes),
            'items' => $codes
        );
        $view = $this->view($response, 200);
        return $this->handleView($view);
    }

    /**
     * Shows the code with the given Id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCodeAction($id)
    {
        // TODO Complete or filter for Fields?
        $code = $this->getDoctrine()
            ->getRepository($this->codeEntity)
            ->find($id);

        if ($code != null) {
            $view = $this->view($code, 200);
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Creates a new code
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postCodesAction()
    {
        $c = $this->getRequest()->get('code');
        $backend = $this->getRequest()->get('backend');
        $frontend = $this->getRequest()->get('frontend');
        $length = $this->getRequest()->get('length');
        $package = $this->getRequest()->get('package');
        $location = $this->getRequest()->get('location');

        if ($c != null && $backend != null && $frontend != null && $location != null && $package != null) {
            $em = $this->getDoctrine()->getManager();

            $translations = $this->getRequest()->get('translations');

            $code = new Code();
            $code->setCode($c);
            $code->setBackend($backend);
            $code->setFrontend($frontend);
            $code->setLength($length);
            $code->setCode($c);
            $code->setPackage($em->getReference($this->packageEntity, $package['id']));
            $code->setLocation($em->getReference($this->locationEntity, $location['id']));

            $em->persist($code);
            $em->flush();

            if ($translations != null) {
                foreach ($translations as $translation) {
                    $t = new Translation();
                    $t->setValue($translation['value']);

                    $t->setCode($code);
                    $code->addTranslation($t);

                    // TODO Catalogue: which format?
                    $t->setCatalogue($em->getReference($this->catalogueEntity, $translation['catalogue']['id']));
                    $em->persist($t);
                }
            }

            $em->flush();

            $view = $this->view($code, 200);
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }
}
