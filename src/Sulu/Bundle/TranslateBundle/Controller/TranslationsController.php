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
use Sulu\Bundle\TranslateBundle\Entity\Catalogue;
use Sulu\Bundle\TranslateBundle\Entity\Code;
use Sulu\Bundle\TranslateBundle\Entity\Translation;
use Sulu\Bundle\TranslateBundle\Entity\TranslationRepository;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

class TranslationsController extends RestController implements ClassResourceInterface
{
    /**
     * @var string
     */
    protected static $entityName = 'SuluTranslateBundle:Translation';

    /**
     * @var string
     */
    protected static $entityNameCode = 'SuluTranslateBundle:Code';

    /**
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors = [];

    /**
     * returns all fields that can be used by list.
     *
     * @Get("translations/fields")
     *
     * @return mixed
     */
    public function getFieldsAction()
    {
        $fieldDescriptors = array_values($this->getFieldDescriptors());

        return $this->handleView($this->view($fieldDescriptors, 200));
    }

    /**
     * @return DoctrineFieldDescriptor[]
     */
    public function getFieldDescriptors()
    {
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
        $this->fieldDescriptors['value'] = new DoctrineFieldDescriptor(
            'value',
            'value',
            self::$entityName,
            'value',
            [],
            true,
            false,
            '',
            '90px'
        );
        $this->fieldDescriptors['suggestion'] = new DoctrineFieldDescriptor(
            'suggestion',
            'suggestion',
            self::$entityName,
            'suggestion',
            []
        );
        $this->fieldDescriptors['code'] = new DoctrineFieldDescriptor(
            'code',
            'code',
            self::$entityNameCode,
            'code',
            [
                self::$entityNameCode => new DoctrineJoinDescriptor(
                        self::$entityNameCode,
                        self::$entityName . '.code'
                    ),
            ]
        );
        $this->fieldDescriptors['backend'] = new DoctrineFieldDescriptor(
            'backend',
            'backend',
            self::$entityNameCode,
            'backend',
            [
                self::$entityNameCode => new DoctrineJoinDescriptor(
                        self::$entityNameCode,
                        self::$entityName . '.code'
                    ),
            ]
        );
        $this->fieldDescriptors['frontend'] = new DoctrineFieldDescriptor(
            'frontend',
            'frontend',
            self::$entityNameCode,
            'frontend',
            [
                self::$entityNameCode => new DoctrineJoinDescriptor(
                        self::$entityNameCode,
                        self::$entityName . '.code'
                    ),
            ]
        );
        $this->fieldDescriptors['length'] = new DoctrineFieldDescriptor(
            'length',
            'length',
            self::$entityNameCode,
            'length',
            [
                self::$entityNameCode => new DoctrineJoinDescriptor(
                        self::$entityNameCode,
                        self::$entityName . '.code'
                    ),
            ]
        );

        return $this->fieldDescriptors;
    }

    /**
     * Lists all the translations or filters the translations by parameters for a single catalogue
     * plus a suggestion.
     *
     * @param Request $request
     * @param $slug
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request, $slug)
    {
        try {
            // find codes by catalogueID
            $codes = $this->getDoctrine()
                ->getRepository('SuluTranslateBundle:Code')
                ->findByCatalogueWithSuggestion($slug);

            // construct response array
            $translations = [];
            for ($i = 0; $i < count($codes); ++$i) {
                $code = $codes[$i];

                // if no translation available set value null
                $value = '';
                $defaultValue = '';
                if (is_array($code['translations']) && count($code['translations']) == 1) {
                    if ($code['translations'][0]['idCatalogues'] == $slug) {
                        $value = $code['translations'][0]['value'];
                    } else {
                        $defaultValue = $code['translations'][0]['value'];
                    }
                } elseif (is_array($code['translations']) && count($code['translations']) == 2) {
                    if ($code['translations'][0]['idCatalogues'] == $slug) {
                        $value = $code['translations'][0]['value'];
                        $defaultValue = $code['translations'][1]['value'];
                    } elseif ($code['translations'][1]['idCatalogues'] == $slug) {
                        $value = $code['translations'][1]['value'];
                        $defaultValue = $code['translations'][0]['value'];
                    }
                }

                $translations[] = [
                    'id' => $code['id'],
                    'value' => $value,
                    'suggestion' => $defaultValue,
                    'code' => [
                        'id' => $code['id'],
                        'code' => $code['code'],
                        'backend' => $code['backend'],
                        'frontend' => $code['frontend'],
                        'length' => $code['length'],
                    ],
                ];
            }

            $response = [
                'total' => count($translations),
                '_embedded' => ['translations' => $translations],
            ];

            $view = $this->view($response, 200);
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * updates an array of translations.
     *
     * @param Request $request
     * @param $slug
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request, $slug)
    {
        $i = 0;
        while ($item = $request->get($i)) {
            $this->saveTranslation($slug, $item);
            ++$i;
        }
        $this->getDoctrine()->getManager()->flush();
        $view = $this->view(null, 204);

        return $this->handleView($view);
    }

    /**
     * save a single translation.
     *
     * @param $catalogueId
     * @param $item
     */
    private function saveTranslation($catalogueId, $item)
    {
        /** @var TranslationRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository(self::$entityName);

        if (isset($item['id']) && $item['id'] != null) {
            // code exists
            /** @var Translation $translation */
            $translation = $repository->getTranslation($item['id'], $catalogueId);
            if ($translation == null) {
                $this->newTranslation($catalogueId, $item);
            } else {
                $translation->setValue($item['value']);
                $translation->getCode()->setCode($item['code']['code']);
                $translation->getCode()->setFrontend($item['code']['frontend']);
                $translation->getCode()->setBackend($item['code']['backend']);
                $translation->getCode()->setLength($item['code']['length']);
            }
        } else {
            // new code
            $this->newCode($catalogueId, $item);
        }
    }

    /**
     * create a single translation.
     *
     * @param $catalogueId
     * @param $item
     */
    private function newTranslation($catalogueId, $item)
    {
        /** @var Code $code */
        $code = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Code')
            ->getCodeById($item['id']);
        /** @var Catalogue $catalogue */
        $catalogue = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Catalogue')
            ->getCatalogueById($catalogueId);

        $translation = new Translation();
        $translation->setCode($code);
        $translation->setCatalogue($catalogue);
        $translation->setValue($item['value']);

        $this->getDoctrine()
            ->getManager()
            ->persist($translation);
    }

    /**
     * create a new code.
     *
     * @param $catalogueId
     * @param $item
     */
    private function newCode($catalogueId, $item)
    {
        /** @var Catalogue $catalogue */
        $catalogue = $this->getDoctrine()
            ->getRepository('SuluTranslateBundle:Catalogue')
            ->getCatalogueById($catalogueId);

        $code = new Code();
        $code->setCode($item['code']['code']);
        $code->setBackend($item['code']['backend']);
        $code->setFrontend($item['code']['frontend']);
        $code->setLength($item['code']['length']);
        $code->setPackage($catalogue->getPackage());

        $this->getDoctrine()
            ->getManager()
            ->persist($code);
        $this->getDoctrine()
            ->getManager()
            ->flush();

        $translation = new Translation();
        $translation->setValue($item['value']);
        $translation->setCode($code);
        $translation->setCatalogue($catalogue);

        $this->getDoctrine()
            ->getManager()
            ->persist($translation);
    }
}
