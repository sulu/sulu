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
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\AudienceTargetingBundle\Admin\AudienceTargetingAdmin;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingParameterException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes target groups available through a REST API.
 *
 * @RouteResource("target-group")
 */
class TargetGroupController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityKey = 'target_groups';

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $listBuilderFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var TargetGroupRepositoryInterface
     */
    private $targetGroupRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        RestHelperInterface $restHelper,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        SerializerInterface $serializer,
        TargetGroupRepositoryInterface $targetGroupRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($viewHandler);
        $this->restHelper = $restHelper;
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->serializer = $serializer;
        $this->targetGroupRepository = $targetGroupRepository;
        $this->entityManager = $entityManager;
    }

    public function getSecurityContext()
    {
        return AudienceTargetingAdmin::SECURITY_CONTEXT;
    }

    /**
     * Returns list of target-groups.
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $listBuilder = $this->listBuilderFactory->create($this->targetGroupRepository->getClassName());

        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('target_groups');
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // If webspaces are concatinated we need to group by id. This happens
        // when no fields are supplied at all OR webspaces are requested as field.
        $fieldsParam = $request->get('fields');
        $fields = \explode(',', $fieldsParam);
        if (null === $fieldsParam || false !== \array_search('webspaceKeys', $fields)) {
            $listBuilder->addGroupBy($fieldDescriptors['id']);
        }

        $results = $listBuilder->execute();
        $list = new ListRepresentation(
            $results,
            static::$entityKey,
            'sulu_audience_targeting.get_target-groups',
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
            $targetGroup = $this->targetGroupRepository->find($id);

            return $targetGroup;
        };

        $view = $this->responseGetById($id, $findCallback);

        return $this->handleView($view);
    }

    /**
     * Handle post request for target group.
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $data = $this->convertFromRequest(\json_decode($request->getContent(), true));
        $targetGroup = $this->deserializeData(\json_encode($data));
        $targetGroup = $this->targetGroupRepository->save($targetGroup);

        $this->entityManager->flush();

        return $this->handleView($this->view($targetGroup));
    }

    /**
     * Handle put request for target group.
     *
     * @param int $id
     *
     * @return Response
     */
    public function putAction(Request $request, $id)
    {
        $jsonData = $request->getContent();
        $data = \json_decode($jsonData, true);

        // Id should be taken of request uri.
        $data['id'] = $id;

        $data = $this->convertFromRequest($data);

        $targetGroup = $this->deserializeData(\json_encode($data));
        $targetGroup = $this->targetGroupRepository->save($targetGroup);
        $this->entityManager->flush();

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

        $this->entityManager->remove($targetGroup);
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * Handle multiple delete requests for target groups.
     *
     * @return Response
     *
     * @throws MissingParameterException
     */
    public function cdeleteAction(Request $request)
    {
        $idsData = $request->get('ids');
        $ids = \explode(',', $idsData);

        if (!\count($ids)) {
            throw new MissingParameterException('TargetGroupController', 'ids');
        }

        $targetGroups = $this->targetGroupRepository->findById($ids);

        foreach ($targetGroups as $targetGroup) {
            $this->entityManager->remove($targetGroup);
        }

        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    private function convertFromRequest(array $data)
    {
        if ($data['webspaceKeys']) {
            $data['webspaces'] = \array_map(function($webspaceKey) {
                return ['webspaceKey' => $webspaceKey];
            }, $data['webspaceKeys']);
        } else {
            $data['webspaces'] = [];
        }

        if (!$data['rules']) {
            $data['rules'] = [];
        }

        // Unset IDs of Conditions, otherwise they won't be able to save as id is null.
        if (\array_key_exists('rules', $data)) {
            foreach ($data['rules'] as $ruleKey => &$rule) {
                if (\array_key_exists('conditions', $rule)) {
                    foreach ($rule['conditions'] as $key => &$condition) {
                        if (\array_key_exists('id', $condition) && \is_null($condition['id'])) {
                            unset($condition['id']);
                        }
                    }
                }
            }
        }

        return $data;
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
        /** @var TargetGroupInterface $result */
        $result = $this->serializer->deserialize(
            $data,
            $this->targetGroupRepository->getClassName(),
            'json'
        );

        return $result;
    }

    /**
     * Returns target group by id. Throws an exception if not found.
     *
     * @param int $id
     *
     * @return TargetGroupInterface
     *
     * @throws EntityNotFoundException
     */
    private function retrieveTargetGroupById($id)
    {
        /** @var TargetGroupInterface $targetGroup */
        $targetGroup = $this->targetGroupRepository->find($id);

        if (!$targetGroup) {
            throw new EntityNotFoundException($this->targetGroupRepository->getClassName(), $id);
        }

        return $targetGroup;
    }
}
