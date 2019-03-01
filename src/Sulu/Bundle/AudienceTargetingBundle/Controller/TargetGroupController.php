<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\DeserializationContext;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleRepositoryInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes target groups available through a REST API.
 *
 * @RouteResource("target-group")
 */
class TargetGroupController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * {@inheritdoc}
     */
    protected static $entityKey = 'target-groups';

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.settings.target-groups';
    }

    /**
     * Returns all fields that can be used by list.
     *
     * @Get("target-groups/fields")
     *
     * @return Response
     */
    public function getFieldsAction()
    {
        return $this->handleView($this->view($this->getFieldDescriptors()));
    }

    /**
     * Returns all fields for rules that can be used by list.
     *
     * @Get("target-groups/rule-fields")
     *
     * @return Response
     */
    public function getRuleFieldsAction()
    {
        return $this->handleView($this->view($this->getRuleFieldDescriptors()));
    }

    /**
     * Returns list of target-groups.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');
        $listBuilder = $factory->create($this->getTargetGroupRepository()->getClassName());

        $fieldDescriptors = $this->getFieldDescriptors();
        $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // If webspaces are concatinated we need to group by id. This happens
        // when no fields are supplied at all OR webspaces are requested as field.
        $fieldsParam = $request->get('fields');
        $fields = explode(',', $fieldsParam);
        if (null === $fieldsParam || false !== array_search('webspaceKeys', $fields)) {
            $listBuilder->addGroupBy($fieldDescriptors['id']);
        }

        $results = $listBuilder->execute();
        $list = new ListRepresentation(
            $results,
            'target-groups',
            'get_target-groups',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $this->handleView($this->view($list, 200));
    }

    /**
     * Returns a target group by id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function getAction($id)
    {
        $findCallback = function($id) {
            $targetGroup = $this->getTargetGroupRepository()->find($id);

            return $targetGroup;
        };

        $view = $this->responseGetById($id, $findCallback);

        return $this->handleView($view);
    }

    /**
     * Handle post request for target group.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $targetGroup = $this->deserializeData($request->getContent());
        $targetGroup = $this->getTargetGroupRepository()->save($targetGroup);

        $this->getEntityManager()->flush();

        return $this->handleView($this->view($targetGroup));
    }

    /**
     * Handle put request for target group.
     *
     * @param Request $request
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        $jsonData = $request->getContent();
        $data = json_decode($jsonData, true);

        // Id should be taken of request uri.
        $data['id'] = $id;

        $targetGroup = $this->deserializeData(json_encode($data));
        $targetGroup = $this->getTargetGroupRepository()->save($targetGroup);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view($targetGroup));
    }

    /**
     * Handle delete request for target group.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $targetGroup = $this->retrieveTargetGroupById($id);

        $this->getEntityManager()->remove($targetGroup);
        $this->getEntityManager()->flush();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * Handle multiple delete requests for target groups.
     *
     * @param Request $request
     *
     * @throws MissingParameterException
     *
     * @return Response
     */
    public function cdeleteAction(Request $request)
    {
        $idsData = $request->get('ids');
        $ids = explode(',', $idsData);

        if (!count($ids)) {
            throw new MissingParameterException('TargetGroupController', 'ids');
        }

        $targetGroups = $this->getTargetGroupRepository()->findById($ids);

        foreach ($targetGroups as $targetGroup) {
            $this->getEntityManager()->remove($targetGroup);
        }

        $this->getEntityManager()->flush();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * Deserializes string into TargetGroup object.
     *
     * @param string $data
     *
     * @return TargetGroupInterface
     */
    private function deserializeData($data)
    {
        $result = $this->get('jms_serializer')->deserialize(
            $data,
            $this->getTargetGroupRepository()->getClassName(),
            'json',
            DeserializationContext::create()
                ->setSerializeNull(true)
        );

        return $result;
    }

    /**
     * Returns array of field-descriptors.
     *
     * @return FieldDescriptorInterface[]
     */
    private function getFieldDescriptors()
    {
        return $this->get('sulu_core.list_builder.field_descriptor_factory')->getFieldDescriptorForClass(
            $this->getTargetGroupRepository()->getClassName()
        );
    }

    /**
     * Returns array of field-descriptors for rules.
     *
     * @return FieldDescriptorInterface[]
     */
    private function getRuleFieldDescriptors()
    {
        return $this->get('sulu_core.list_builder.field_descriptor_factory')->getFieldDescriptorForClass(
            $this->getTargetGroupRuleRepository()->getClassName()
        );
    }

    /**
     * Returns target group by id. Throws an exception if not found.
     *
     * @param int $id
     *
     * @throws EntityNotFoundException
     *
     * @return TargetGroupInterface
     */
    private function retrieveTargetGroupById($id)
    {
        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $this->getTargetGroupRepository()->find($id);

        if (!$targetGroup) {
            throw new EntityNotFoundException($this->getTargetGroupRepository()->getClassName(), $id);
        }

        return $targetGroup;
    }

    /**
     * @return TargetGroupRepositoryInterface
     */
    private function getTargetGroupRepository()
    {
        return $this->get('sulu.repository.target_group');
    }

    /**
     * @return TargetGroupRuleRepositoryInterface
     */
    private function getTargetGroupRuleRepository()
    {
        return $this->get('sulu.repository.target_group_rule');
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->get('doctrine.orm.entity_manager');
    }
}
