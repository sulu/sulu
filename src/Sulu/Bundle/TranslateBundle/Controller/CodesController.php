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
use Sulu\Bundle\TranslateBundle\Entity\CodeRepository;
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
    private $translationEntity = 'SuluTranslateBundle:Translation';

    /**
     * Lists all the codes or filters the codes by parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCodesAction()
    {
        $listHelper = $this->get('sulu_core.list_rest_helper');
        $limit = $listHelper->getLimit();
        $offset = $listHelper->getOffset();
        $sorting = $listHelper->getSorting();

        /** @var CodeRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository($this->codeEntity);

        $catalogueId = $this->getRequest()->get('catalogueId');
        $packageId = $this->getRequest()->get('packageId');
        if ($catalogueId != null) {
            $codes = $repository->findByCatalogue($catalogueId);
        } else {
            if ($packageId != null) {
                $codes = $repository->findByPackage($packageId);
            } else {
                $codes = $repository->findGetAll($limit, $offset, $sorting);
            }
        }

        $response = array(
            'total' => count($codes),
            'items' => $codes
        );
        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Lists all the codes or filters the codes by parameters
     * Special function for lists
     * route /codes/list
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listCodesAction()
    {
        $listHelper = $this->get('sulu_core.list_rest_helper');

        $where = array();
        $packageId = $this->getRequest()->get('packageId');
        if ($packageId != null) {
            $where['package_id'] = $packageId;
        }
        $catalogueId = $this->getRequest()->get('catalogueId');
        if ($catalogueId != null) {
            $where['translations_catalogue_id'] = $catalogueId;
        }

        $codes = $listHelper->find($this->codeEntity, $where);

        $response = array(
            'total' => sizeof($codes),
            'items' => $codes
        );
        $view = $this->view($response, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the code with the given id
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
        $translations = $this->getRequest()->get('translations');

        if ($c != null && $backend != null && $frontend != null && $location != null && $package != null) {
            $em = $this->getDoctrine()->getManager();

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

    /**
     * Updates the code for the given id
     * @param integer $id The id of the package to update
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putCodesAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Code $code */
        $code = $this->getDoctrine()
            ->getRepository($this->codeEntity)
            ->find($id);

        $c = $this->getRequest()->get('code');
        $backend = $this->getRequest()->get('backend');
        $frontend = $this->getRequest()->get('frontend');
        $length = $this->getRequest()->get('length');
        $package = $this->getRequest()->get('package');
        $location = $this->getRequest()->get('location');
        $translations = $this->getRequest()->get('translations');

        $translationRepository = $this->getDoctrine()
            ->getRepository($this->translationEntity);

        if (!$code) {
            // No Code exists
            $view = $this->view(null, 400);
        } else {
            $code->setCode($c);
            $code->setBackend($backend);
            $code->setFrontend($frontend);
            $code->setLength($length);
            $code->setPackage($em->getReference($this->packageEntity, $package['id']));
            $code->setLocation($em->getReference($this->locationEntity, $location['id']));

            if ($translations != null && sizeof($translations) > 0) {
                foreach ($translations as $translation) {
                    /** @var Translation $t */
                    $t = $translationRepository->findOneBy(
                        array(
                            'code' => $code->getId(),
                            'catalogue' => $translation['catalogue']['id']
                        )
                    );

                    if ($t != null) {
                        $t->setValue($translation['value']);
                        $t->setCode($code);
                        $t->setCatalogue($em->getReference($this->catalogueEntity, $translation['catalogue']['id']));
                    } else {
                        // Create a new Translation
                        $t = new Translation();
                        $t->setValue($translation['value']);
                        $t->setCode($code);
                        $code->addTranslation($t);
                        $t->setCatalogue($em->getReference($this->catalogueEntity, $translation['catalogue']['id']));
                        $em->persist($t);
                    }
                }
            }

            $em->flush();
            $view = $this->view($code, 200);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the code with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteCodeAction($id)
    {
        $code = $this->getDoctrine()
            ->getRepository($this->codeEntity)
            ->find($id);

        if ($code != null) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($code);
            $em->flush();

            $view = $this->view(null, 204);

        } else {
            $view = $this->view(null, 404);

        }

        return $this->handleView($view);
    }
}
