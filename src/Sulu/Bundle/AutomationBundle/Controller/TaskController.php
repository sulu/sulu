<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Routing\ClassResourceInterface;
use JMS\Serializer\DeserializationContext;
use Sulu\Bundle\AutomationBundle\Entity\Task;
use Sulu\Bundle\AutomationBundle\Exception\TaskNotFoundException;
use Sulu\Bundle\AutomationBundle\Tasks\Manager\TaskManagerInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides api for tasks.
 */
class TaskController extends RestController implements ClassResourceInterface
{
    /**
     * Returns list of tasks.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');
        $fieldDescriptors = $this->getFieldDescriptors();

        $listBuilder = $factory->create(Task::class);
        $restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        if ($entityClass = $request->get('entity-class')) {
            $listBuilder->where($fieldDescriptors['entityClass'], $entityClass);
        }
        if ($entityId = $request->get('entity-id')) {
            $listBuilder->where($fieldDescriptors['entityId'], $entityId);
        }

        return $this->handleView(
            $this->view(
                new ListRepresentation(
                    $listBuilder->execute(),
                    'tasks',
                    'get_tasks',
                    $request->query->all(),
                    $listBuilder->getCurrentPage(),
                    $listBuilder->getLimit(),
                    $listBuilder->count()
                )
            )
        );
    }

    /**
     * Returns task for given id.
     *
     * @param int $id
     *
     * @return Response
     *
     * @throws TaskNotFoundException
     */
    public function getAction($id)
    {
        $manager = $this->getTaskManager();

        return $this->handleView($this->view($manager->findById($id)));
    }

    /**
     * Create new task.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function postAction(Request $request)
    {
        $manager = $this->getTaskManager();
        $task = $this->get('serializer')->deserialize(
            json_encode($request->request->all()),
            Task::class,
            'json',
            DeserializationContext::create()->setGroups(['api'])
        );
        $task = $manager->create($task);

        $this->getEntityManager()->flush();

        return $this->handleView($this->view($task));
    }

    /**
     * Update task with given id.
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function putAction($id, Request $request)
    {
        $task = $this->get('serializer')->deserialize(
            json_encode(array_merge(['id' => $id], $request->request->all())),
            Task::class,
            'json',
            DeserializationContext::create()->setGroups(['api'])
        );

        $manager = $this->getTaskManager();
        $manager->update($task);

        $this->getEntityManager()->flush();

        return $this->handleView($this->view($task));
    }

    /**
     * Removes task with given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $manager = $this->getTaskManager();
        $manager->remove($id);

        $this->getEntityManager()->flush();

        return $this->handleView($this->view());
    }

    /**
     * Removes multiple tasks identified by ids parameter.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cdeleteAction(Request $request)
    {
        $manager = $this->getTaskManager();

        $ids = array_filter(explode(',', $request->get('ids')));
        foreach ($ids as $id) {
            $manager->remove($id);
        }

        $this->getEntityManager()->flush();

        return $this->handleView($this->view());
    }

    /**
     * Returns field-descriptors for task-entity.
     *
     * @return FieldDescriptorInterface[]
     */
    private function getFieldDescriptors()
    {
        return $this->get('sulu_core.list_builder.field_descriptor_factory')->getFieldDescriptorForClass(Task::class);
    }

    /**
     * Returns task-manager.
     *
     * @return TaskManagerInterface
     */
    private function getTaskManager()
    {
        return $this->get('sulu_automation.tasks.manager');
    }

    /**
     * Returns entity-manager.
     *
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return $this->get('doctrine.orm.entity_manager');
    }
}
