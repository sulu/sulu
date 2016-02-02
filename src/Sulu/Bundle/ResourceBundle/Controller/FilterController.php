<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ResourceBundle\Resource\Exception\ConditionGroupMismatchException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterDependencyNotFoundException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterNotFoundException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\MissingFeatureException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\MissingFilterAttributeException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\MissingFilterException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\UnknownContextException;
use Sulu\Bundle\ResourceBundle\Resource\FilterManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes filters available through a REST API.
 */
class FilterController extends RestController implements ClassResourceInterface
{
    protected static $groupConditionEntityName = 'SuluResourceBundle:GroupCondition';

    protected static $entityName = 'SuluResourceBundle:Filter';

    protected static $entityKey = 'filters';

    /**
     * Retrieves a filter by id.
     *
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale) {
                return $this->getManager()->findByIdAndLocale($id, $locale);
            }
        );

        return $this->handleView($view);
    }

    /**
     * Returns a list of filters.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        try {
            // check if context exists and filters are enabled for the given context
            $context = $request->get('context');

            if (!$this->getManager()->hasContext($context)) {
                throw new UnknownContextException($context);
            }

            if (!$this->getManager()->isFeatureEnabled($context, 'filters')) {
                throw new MissingFeatureException($context, 'filters');
            }

            if ($request->get('flat') == 'true') {
                $list = $this->getListRepresentation($request);
            } else {
                $list = new CollectionRepresentation(
                    $this->getManager()->findFiltersForUserAndContext(
                        $context,
                        $this->getUser()->getId(),
                        $this->getLocale($request)
                    ),
                    self::$entityKey
                );
            }
            $view = $this->view($list, 200);
        } catch (UnknownContextException $exc) {
            $exception = new RestException($exc->getMessage());
            $view = $this->view($exception->toArray(), 400);
        } catch (MissingFeatureException $exc) {
            $exception = new RestException($exc->getMessage());
            $view = $this->view($exception->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a list representation.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return Sulu\Component\Rest\ListBuilder\ListRepresentation
     */
    private function getListRepresentation($request)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');
        $fieldDescriptors = $this->getManager()->getListFieldDescriptors($this->getLocale($request));

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');
        $listBuilder = $factory->create(self::$entityName);
        $restHelper->initializeListBuilder(
            $listBuilder,
            $fieldDescriptors
        );

        if ($request->get('context')) {
            $listBuilder->where($fieldDescriptors['context'], $request->get('context'));
        }

        // return all filters created by the user or without user
        $userCondition = [$this->getUser()->getId(), null];
        $listBuilder->in($fieldDescriptors['user'], $userCondition);

        $list = new ListRepresentation(
            $listBuilder->execute(),
            self::$entityKey,
            'get_filters',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $list;
    }

    /**
     * Creates and stores a new filter.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $filter = $this->getManager()->save(
                $request->request->all(),
                $this->getLocale($request),
                $this->getUser()->getId()
            );
            $view = $this->view($filter, 200);
        } catch (FilterDependencyNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        } catch (MissingFilterException $exc) {
            $exception = new MissingArgumentException(self::$entityName, $exc->getFilter());
            $view = $this->view($exception->toArray(), 400);
        } catch (MissingFilterAttributeException $exc) {
            $exception = new MissingArgumentException(self::$entityName, $exc->getAttribute());
            $view = $this->view($exception->toArray(), 400);
        } catch (ConditionGroupMismatchException $exc) {
            $exception = new InvalidArgumentException(self::$groupConditionEntityName, $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        } catch (UnknownContextException $exc) {
            $exception = new RestException($exc->getMessage());
            $view = $this->view($exception->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Change a filter by the given id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $id the attribute id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            $filter = $this->getManager()->save(
                $request->request->all(),
                $this->getLocale($request),
                $this->getUser()->getId(),
                $id
            );
            $view = $this->view($filter, 200);
        } catch (FilterDependencyNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        } catch (FilterNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 404);
        } catch (MissingFilterException $exc) {
            $exception = new MissingArgumentException(self::$entityName, $exc->getFilter());
            $view = $this->view($exception->toArray(), 400);
        } catch (ConditionGroupMismatchException $exc) {
            $exception = new InvalidArgumentException(self::$groupConditionEntityName, $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        } catch (UnknownContextException $exc) {
            $exception = new RestException($exc->getMessage());
            $view = $this->view($exception->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Delete an filter with the given id.
     *
     * @param int $id the attribute id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        try {
            $this->getManager()->delete($id);
            $view = $this->view($id, 204);
        } catch (FilterNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * Delete an filter with the given id.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cdeleteAction(Request $request)
    {
        $ids = explode(',', $request->get('ids'));
        if ($ids && count($ids) > 0) {
            try {
                $this->getManager()->batchDelete($ids);
                $view = $this->view($ids, 204);
            } catch (FilterNotFoundException $exc) {
                $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
                $view = $this->view($exception->toArray(), 404);
            }
        } else {
            $exception = new InvalidArgumentException(static::$entityName, $ids);
            $view = $this->view($exception->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * returns all fields that can be used by list.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return mixed
     */
    public function fieldsAction(Request $request)
    {
        return $this->handleView(
            $this->view(array_values($this->getManager()->getFieldDescriptors($this->getLocale($request))), 200)
        );
    }

    /**
     * Returns the manager for filters.
     *
     * @return FilterManagerInterface
     */
    protected function getManager()
    {
        return $this->get('sulu_resource.filter_manager');
    }
}
