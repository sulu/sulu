<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\PageTree;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AutomationBundle\Entity\Task;
use Sulu\Bundle\AutomationBundle\Tasks\Manager\TaskManagerInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Schedules the route-update task.
 */
class AutomationPageTreeUpdater implements PageTreeUpdaterInterface
{
    /**
     * @var TaskManagerInterface
     */
    private $taskManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        TaskManagerInterface $taskManager,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack
    ) {
        $this->taskManager = $taskManager;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function update(BasePageDocument $document)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $task = new Task();
        $task->setEntityClass(BasePageDocument::class)
            ->setEntityId($document->getUuid())
            ->setLocale($document->getLocale())
            ->setHandlerClass(PageTreeRouteUpdateHandler::class)
            ->setSchedule(new \DateTime())
            ->setHost($request->getHost())
            ->setScheme($request->getScheme());

        $this->taskManager->create($task);
        $this->entityManager->flush();
    }
}
