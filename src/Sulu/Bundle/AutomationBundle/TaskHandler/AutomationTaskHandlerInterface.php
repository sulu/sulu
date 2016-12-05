<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\TaskHandler;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Task\Handler\TaskHandlerInterface;

/**
 * Interface for automation task-handler.
 */
interface AutomationTaskHandlerInterface extends TaskHandlerInterface
{
    /**
     * Configures options-resolver to validate workload.
     *
     * @param OptionsResolver $optionsResolver
     *
     * @return OptionsResolver
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver);

    /**
     * Returns true if handler supports given class.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function supports($entityClass);

    /**
     * Returns configuration for this task-handler.
     *
     * @return TaskHandlerConfiguration
     */
    public function getConfiguration();
}
