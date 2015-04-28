<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ResourceBundle\Api\Filter;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterDependencyNotFoundException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\FilterNotFoundException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\MissingFilterAttributeException;
use Sulu\Bundle\ResourceBundle\Resource\Exception\MissingFilterException;
use Sulu\Bundle\ResourceBundle\Resource\FilterManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FilterController
 * @package Sulu\Bundle\ResourceBundle\Controller
 */
class FilterController extends RestController implements ClassResourceInterface
{
    protected static $entityName = 'SuluResourceBundle:Filter';

    protected static $entityKey = 'filters';

    /**
     * Retrieves a filter by id
     *
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale) {
                /** @var Filter $filter */
                $filter = $this->getManager()->findByIdAndLocale($id, $locale);

                return $filter;
            }
        );

        return $this->handleView($view);
    }

    /**
     * Returns a list of attributes
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ($request->get('flat') == 'true') {
            $list = $this->getListRepresentation($request);
        } else {
            $list = new CollectionRepresentation(
                $this->getManager()->findAllByLocale($this->getLocale($request)),
                self::$entityKey
            );
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Returns a list representation
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Sulu\Component\Rest\ListBuilder\ListRepresentation
     */
    private function getListRepresentation($request)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');
        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');
        $listBuilder = $factory->create(self::$entityName);
        $restHelper->initializeListBuilder(
            $listBuilder,
            $this->getManager()->getFieldDescriptors($this->getLocale($request))
        );
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
        }

        return $this->handleView($view);
    }

    /**
     * Change a filter by the given id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id the attribute id
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
        }

        return $this->handleView($view);
    }

    /**
     * Delete an filter with the given id.
     *
     * @param integer $id the attribute id
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
     * returns all fields that can be used by list
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public function fieldsAction(Request $request)
    {
        return $this->handleView(
            $this->view(array_values($this->getManager()->getFieldDescriptors($this->getLocale($request))), 200)
        );
    }

    /**
     * Returns the manager for filters
     *
     * @return FilterManagerInterface
     */
    protected function getManager()
    {
        return $this->get('sulu_resource.filter_manager');
    }
}
