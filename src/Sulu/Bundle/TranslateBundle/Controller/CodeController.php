<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\CodeRepository;
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Sulu\Bundle\TranslateBundle\Translate\TranslateCollectionRepresentation;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the translation codes accessible trough an REST-API.
 */
class CodeController extends RestController implements ClassResourceInterface
{
    protected static $entityName = 'SuluTranslateBundle:Code';
    protected static $catalogueEntity = 'SuluTranslateBundle:Catalogue';
    protected static $packageEntity = 'SuluTranslateBundle:Package';
    protected static $locationEntity = 'SuluTranslateBundle:Location';
    protected static $translationEntity = 'SuluTranslateBundle:Translation';

    protected static $entityKey = 'codes';

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors = [];

    /**
     * returns all fields that can be used by list.
     *
     * @Get("codes/fields")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getFieldsAction(Request $request)
    {
        $fieldDescriptors = array_values($this->getFieldDescriptors($request->getLocale()));

        return $this->handleView($this->view($fieldDescriptors, 200));
    }

    /**
     * Lists all the codes or filters the codes by parameters.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ($request->get('flat') == 'true') {
            // flat structure
            return $this->listAction($request);
        } else {
            /* @var ListRestHelperInterface $listRestHelper */
            $listHelper = $this->get('sulu_core.list_rest_helper');
            $limit = $listHelper->getLimit();
            $offset = $listHelper->getOffset();
            $sortColumn = $listHelper->getSortColumn();
            $sorting = ['id' => 'ASC'];
            if ($sortColumn) {
                $sortOrder = $listHelper->getSortOrder();
                $sorting = [$sortColumn => $sortOrder];
            }

            /** @var CodeRepository $repository */
            $repository = $this->getDoctrine()
                ->getRepository(self::$entityName);

            $catalogueId = $request->get('catalogueId');
            $packageId = $request->get('packageId');
            if ($catalogueId != null) {
                // TODO Add limit, offset & sorting for find by filter catalogue
                $codes = $repository->findByCatalogue($catalogueId);
            } else {
                if ($packageId != null) {
                    // TODO Add limit, offset & sorting for find by filter package
                    $codes = $repository->findByPackage($packageId);
                } else {
                    $codes = $repository->findGetAll($limit, $offset, $sorting);
                }
            }

            $list = new TranslateCollectionRepresentation(
                $codes,
                self::$entityKey
            );
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Lists all the codes or filters the codes by parameters
     * Special function for lists
     * route /codes/list.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function listAction(Request $request)
    {
        $filter = [];

        $catalogueId = $request->get('catalogueId');
        $packageId = $request->get('packageId');

        if ($packageId) {
            $filter['packageId'] = $packageId;
        }

        if ($catalogueId) {
            $filter['catalogueId'] = $catalogueId;
        }

        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $listBuilder = $factory->create(self::$entityName);

        $restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors($request->getLocale()));

        foreach ($filter as $key => $value) {
            $listBuilder->where($this->getFieldDescriptor($key), $value);
        }

        $list = new ListRepresentation(
            $listBuilder->execute(),
            self::$entityKey,
            'get_codes',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Shows the code with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        // TODO Complete or filter for Fields?
        $find = function ($id) {
            return $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->getCodeById($id);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new code.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $c = $request->get('code');
        $backend = $request->get('backend');
        $frontend = $request->get('frontend');
        $length = $request->get('length');
        $package = $request->get('package');
        $location = $request->get('location');
        $translations = $request->get('translations');

        if ($c != null && $backend != null && $frontend != null && $location != null && $package != null) {
            $em = $this->getDoctrine()->getManager();

            $code = new Code();
            $code->setCode($c);
            $code->setBackend($backend);
            $code->setFrontend($frontend);
            $code->setLength($length);
            $code->setCode($c);
            $code->setPackage($em->getReference(self::$packageEntity, $package['id']));
            $code->setLocation($em->getReference(self::$locationEntity, $location['id']));

            $em->persist($code);
            $em->flush();

            if ($translations != null) {
                foreach ($translations as $translation) {
                    $t = new Translation();
                    $t->setValue($translation['value']);

                    $t->setCode($code);
                    $code->addTranslation($t);

                    // TODO Catalogue: which format?
                    $t->setCatalogue($em->getReference(self::$catalogueEntity, $translation['catalogue']['id']));
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
     * Updates the code for the given id.
     *
     * @param Request $request
     * @param int     $id      The id of the package to update
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Code $code */
        $code = $this->getDoctrine()
            ->getRepository(self::$entityName)
            ->getCodeById($id);

        $c = $request->get('code');
        $backend = $request->get('backend');
        $frontend = $request->get('frontend');
        $length = $request->get('length');
        $package = $request->get('package');
        $location = $request->get('location');
        $translations = $request->get('translations');

        $translationRepository = $this->getDoctrine()
            ->getRepository(self::$translationEntity);

        if (!$code) {
            // No Code exists
            $view = $this->view(null, 404);
        } else {
            $code->setCode($c);
            $code->setBackend($backend);
            $code->setFrontend($frontend);
            $code->setLength($length);
            $code->setPackage($em->getReference(self::$packageEntity, $package['id']));
            $code->setLocation($em->getReference(self::$locationEntity, $location['id']));

            if ($translations != null && count($translations) > 0) {
                foreach ($translations as $translationData) {
                    /** @var Translation $translation */
                    $translation = $translationRepository->findOneBy(
                        [
                            'code' => $code->getId(),
                            'catalogue' => $translationData['catalogue']['id'],
                        ]
                    );

                    if ($translation != null) {
                        $translation->setValue($translationData['value']);
                        $translation->setCode($code);
                        $translation->setCatalogue($em->getReference(self::$catalogueEntity, $translationData['catalogue']['id']));
                    } else {
                        // Create a new Translation
                        $translation = new Translation();
                        $translation->setValue($translationData['value']);
                        $translation->setCode($code);
                        $code->addTranslation($translation);
                        $translation->setCatalogue($em->getReference(self::$catalogueEntity, $translationData['catalogue']['id']));
                        $em->persist($translation);
                    }
                }
            }

            $em->flush();
            $view = $this->view($code, 200);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the code with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $code = $this->getDoctrine()
                ->getRepository(self::$entityName)
                ->getCodeById($id);

            if (!$code) {
                throw new EntityNotFoundException(self::$entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($code);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * @param string $locale
     *
     * @return DoctrineFieldDescriptor[]
     */
    private function getFieldDescriptors($locale)
    {
        $locale = $this->getDoctrine()->getConnection()->quote(strtoupper($locale));
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$entityName,
            'id',
            [],
            true,
            false,
            '',
            '50px'
        );
        $this->fieldDescriptors['code'] = new DoctrineFieldDescriptor(
            'code',
            'code',
            self::$entityName,
            'code',
            [],
            true,
            false,
            '',
            '90px'
        );
        $this->fieldDescriptors['backend'] = new DoctrineFieldDescriptor('backend', 'backend', self::$entityName);
        $this->fieldDescriptors['frontend'] = new DoctrineFieldDescriptor('frontend', 'frontend', self::$entityName);
        $this->fieldDescriptors['length'] = new DoctrineFieldDescriptor('length', 'length', self::$entityName);
        $this->fieldDescriptors['packageId'] = new DoctrineFieldDescriptor(
            'id',
            'packageId',
            self::$packageEntity,
            'package',
            [
                self::$packageEntity => new DoctrineJoinDescriptor(
                        self::$packageEntity,
                        self::$entityName . '.package'
                    ),
            ]
        );
        $this->fieldDescriptors['catalogueId'] = new DoctrineFieldDescriptor(
            'id',
            'catalogueId',
            self::$catalogueEntity,
            'catalogue',
            [
                self::$packageEntity => new DoctrineJoinDescriptor(
                        self::$packageEntity,
                        self::$entityName . '.package'
                    ),
                self::$catalogueEntity => new DoctrineJoinDescriptor(
                        self::$catalogueEntity,
                        self::$packageEntity . '.catalogues',
                        self::$catalogueEntity . '.locale = ' . $locale
                    ),
            ]
        );
        $this->fieldDescriptors['location_name'] = new DoctrineFieldDescriptor(
            'name',
            'location_name',
            self::$locationEntity,
            'location',
            [
                self::$locationEntity => new DoctrineJoinDescriptor(
                        self::$locationEntity,
                        self::$entityName . '.location'
                    ),
            ]
        );

        return $this->fieldDescriptors;
    }

    /**
     * @param $key
     *
     * @return DoctrineFieldDescriptor
     */
    private function getFieldDescriptor($key)
    {
        return $this->fieldDescriptors[$key];
    }
}
