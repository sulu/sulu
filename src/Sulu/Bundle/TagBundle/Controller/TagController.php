<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
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
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes tag available through.
 */
class TagController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = 'SuluTagBundle:Tag';

    protected static $entityKey = 'tags';

    protected $unsortable = [];

    protected $bundlePrefix = 'tags.';

    /**
     * @return TagManagerInterface
     */
    private function getManager()
    {
        return $this->get('sulu_tag.tag_manager');
    }

    /**
     * Returns a single tag with the given id.
     *
     * @param $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        $view = $this->responseGetById(
            $id,
            function($id) {
                return $this->getManager()->findById($id);
            }
        );

        $context = new Context();
        $context->setGroups(['partialTag']);
        $view->setContext($context);

        return $this->handleView($view);
    }

    /**
     * returns all tags.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        if ('true' == $request->get('flat')) {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $tagEntityName = $this->getParameter('sulu.model.tag.class');

            $fieldDescriptors = $this->get('sulu_core.list_builder.field_descriptor_factory')
                ->getFieldDescriptors('tags');
            $listBuilder = $factory->create($tagEntityName);

            $names = array_filter(explode(',', $request->get('names', '')));
            if (count($names) > 0) {
                $listBuilder->in($fieldDescriptors['name'], $names);
            }

            $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'sulu_tag.get_tags',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
            $view = $this->view($list, 200);
        } else {
            $list = new CollectionRepresentation($this->getManager()->findAll(), self::$entityKey);

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($list, 200)->setContext($context);
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
            if (null == $name) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->getManager()->save($this->getData($request));

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($tag)->setContext($context);
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
            if (null == $name) {
                throw new MissingArgumentException(self::$entityName, 'name');
            }

            $tag = $this->getManager()->save($this->getData($request), $id);

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($tag)->setContext($context);
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
     * @return Response
     */
    public function deleteAction($id)
    {
        $delete = function($id) {
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
     * @param Request $request
     *
     * @return Response
     */
    public function postMergeAction(Request $request)
    {
        try {
            $srcTagIds = explode(',', $request->get('src'));
            $destTagId = $request->get('dest');

            $destTag = $this->getManager()->merge($srcTagIds, $destTagId);

            $view = $this->view(
                null,
                303,
                [
                    'location' => $this->get('router')->generate('sulu_tag.get_tag', ['id' => $destTag->getId()]),
                ]
            );
        } catch (TagNotFoundException $exc) {
            $entityNotFoundException = new EntityNotFoundException(self::$entityName, $exc->getId());
            $view = $this->view($entityNotFoundException->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function cpatchAction(Request $request)
    {
        try {
            $tags = [];

            $i = 0;
            while ($item = $request->get($i)) {
                if (isset($item['id'])) {
                    $tags[] = $this->getManager()->save($item, $item['id']);
                } else {
                    $tags[] = $this->getManager()->save($item);
                }
                ++$i;
            }
            $this->getDoctrine()->getManager()->flush();

            $context = new Context();
            $context->setGroups(['partialTag']);
            $view = $this->view($tags)->setContext($context);
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

    /**
     * Get data.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getData(Request $request)
    {
        return $request->request->all();
    }
}
