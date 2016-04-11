<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use JMS\Serializer\SerializationContext;
use Sulu\Bundle\TagBundle\Controller\Exception\ConstraintViolationException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagAlreadyExistsException;
use Sulu\Bundle\TagBundle\Tag\Exception\TagNotFoundException;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes tag available through.
 */
class TagController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = 'SuluTagBundle:Tag';

    protected static $entityKey = 'tags';

    protected $unsortable = [];

    protected $fieldsDefault = [
        'name',
    ];

    protected $fieldsValidation = [
        'name' => [
            'required' => true,
        ],
    ];

    protected $fieldsEditable = [
        'name',
    ];

    protected $fieldsExcluded = [];

    protected $fieldsHidden = [
        'created',
        'id',
        'creator_contact_lastName',
        'changed',
    ];
    protected $fieldsRelations = [
        'creator_contact_lastName',
    ];
    protected $fieldsSortOrder = [
        '0' => 'name',
        '1' => 'creator_contact_lastName',
        '2' => 'changed',
    ];

    protected $fieldsTranslationKeys = [
        'name' => 'tags.name',
        'creator_contact_lastName' => 'tags.author',
    ];

    protected $bundlePrefix = 'tags.';

    /**
     * @return TagManagerInterface
     */
    private function getManager()
    {
        return $this->get('sulu_tag.tag_manager');
    }

    /**
     * returns all fields that can be used by list.
     *
     * @Get("tags/fields")
     *
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->handleView($this->view(array_values($this->getManager()->getFieldDescriptors())));
    }

    /**
     * persists a setting.
     *
     * @Put("tags/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Returns a single tag with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function ($id) {
                return $this->getManager()->findById($id);
            }
        );

        $view->setSerializationContext(SerializationContext::create()->setGroups(['partialTag']));

        return $this->handleView($view);
    }

    /**
     * returns all tags.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ($request->get('flat') == 'true') {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $fieldDescriptors = $this->getManager()->getFieldDescriptors();
            $listBuilder = $factory->create(self::$entityName);

            $ids = array_filter(explode(',', $request->get('ids', '')));
            if (count($ids) > 0) {
                $listBuilder->in($fieldDescriptors['id'], $ids);
            }

            $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_tags',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
            $view = $this->view($list, 200);
        } else {
            $list = new CollectionRepresentation(
                $this->getManager()->findAll(),
                self::$entityKey
            );
            $view = $this->view($list, 200);
            $view->setSerializationContext(SerializationContext::create()->setGroups(['partialTag']));
        }

        return $this->handleView($view);
    }

    /**
     * Inserts a new tag.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        try {
            if ($name == null) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->getManager()->save(['name' => $name], $this->getUser()->getId());

            $view = $this->view($tag, 200);
            $view->setSerializationContext(SerializationContext::create()->setGroups(['partialTag']));
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException(
                'A tag with the name "' . $exc->getName() . '"already exists!',
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME
            );
            $view = $this->view($cvExistsException->toArray(), 400);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the tag with the given ID.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function putAction(Request $request, $id)
    {
        $name = $request->get('name');

        try {
            if ($name == null) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->getManager()->save(['name' => $name], $this->getUser()->getId(), $id);

            $view = $this->view($tag, 200);
            $view->setSerializationContext(SerializationContext::create()->setGroups(['partialTag']));
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException(
                'A tag with the name "' . $exc->getName() . '"already exists!',
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME
            );
            $view = $this->view($cvExistsException->toArray(), 400);
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException(self::$entityName, $id);
            $view = $this->view($entityNotFoundException->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the tag with the given ID.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            try {
                $this->getManager()->delete($id);
            } catch (TagNotFoundException $tnfe) {
                throw new EntityNotFoundException(self::$entityName, $id);
            }
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * POST Route annotation.
     *
     * @Post("/tags/merge")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postMergeAction(Request $request)
    {
        try {
            $srcTagIds = explode(',', $request->get('src'));
            $destTagId = $request->get('dest');

            $destTag = $this->getManager()->merge($srcTagIds, $destTagId);

            $view = $this->view(null, 303, ['location' => $destTag->getLinks()['self']]);
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException(self::$entityName, $exc->getId());
            $view = $this->view($entityNotFoundException->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * TODO: find out why pluralization does not work for this patch action
     * ISSUE: https://github.com/sulu-cmf/SuluTagBundle/issues/6.
     *
     * @Route("/tags", name="tags")
     * updates an array of tags
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request)
    {
        try {
            $tags = [];

            $i = 0;
            while ($item = $request->get($i)) {
                if (isset($item['id'])) {
                    $tags[] = $this->getManager()->save($item, $item['id']);
                } else {
                    $tags[] = $this->getManager()->save($item, null);
                }
                ++$i;
            }
            $this->getDoctrine()->getManager()->flush();
            $view = $this->view($tags, 200);
            $view->setSerializationContext(SerializationContext::create()->setGroups(['partialTag']));
        } catch (TagAlreadyExistsException $exc) {
            $cvExistsException = new ConstraintViolationException(
                'A tag with the name "' . $exc->getName() . '"already exists!',
                'name',
                ConstraintViolationException::EXCEPTION_CODE_NON_UNIQUE_NAME
            );
            $view = $this->view($cvExistsException->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.settings.tags';
    }
}
