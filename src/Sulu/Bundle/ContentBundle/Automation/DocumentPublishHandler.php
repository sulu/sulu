<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Automation;

use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\TaskHandler\TaskHandlerConfiguration;
use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides handler for publishing documents.
 */
class DocumentPublishHandler implements AutomationTaskHandlerInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($workload)
    {
        $document = $this->documentManager->find($workload['id'], $workload['locale']);
        $this->documentManager->publish($document, $workload['locale']);
        $this->documentManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver)
    {
        return $optionsResolver
            ->setRequired(['id', 'locale'])
            ->setAllowedTypes('id', 'string')
            ->setAllowedTypes('locale', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entityClass)
    {
        return is_subclass_of($entityClass, WorkflowStageBehavior::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return TaskHandlerConfiguration::create('sulu_content.task_handler.publish');
    }
}
