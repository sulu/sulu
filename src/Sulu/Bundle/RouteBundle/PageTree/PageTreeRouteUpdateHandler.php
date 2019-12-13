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
use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\TaskHandler\TaskHandlerConfiguration;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Task\Executor\RetryTaskHandlerInterface;
use Task\Lock\LockingTaskHandlerInterface;

/**
 * Task-Handler to update page-tree-routes.
 */
class PageTreeRouteUpdateHandler implements AutomationTaskHandlerInterface, LockingTaskHandlerInterface, RetryTaskHandlerInterface
{
    /**
     * @var PageTreeUpdaterInterface
     */
    private $routeUpdater;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        PageTreeUpdaterInterface $routeUpdater,
        DocumentManagerInterface $documentManager,
        EntityManagerInterface $entityManager
    ) {
        $this->routeUpdater = $routeUpdater;
        $this->documentManager = $documentManager;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver): OptionsResolver
    {
        return $optionsResolver->setRequired(['id', 'locale'])
            ->setAllowedTypes('id', 'string')
            ->setAllowedTypes('locale', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entityClass): bool
    {
        return is_subclass_of($entityClass, BasePageDocument::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): TaskHandlerConfiguration
    {
        return TaskHandlerConfiguration::create('sulu_route.update_page_tree_route');
    }

    /**
     * {@inheritdoc}
     */
    public function handle($workload)
    {
        $this->entityManager->beginTransaction();

        try {
            $this->routeUpdater->update($this->documentManager->find($workload['id'], $workload['locale']));

            $this->documentManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLockKey($workload)
    {
        return self::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximumAttempts()
    {
        return 3;
    }
}
