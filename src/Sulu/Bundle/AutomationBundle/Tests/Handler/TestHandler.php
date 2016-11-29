<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AutomationBundle\Tests\Handler;

use Sulu\Bundle\AutomationBundle\TaskHandler\AutomationTaskHandlerInterface;
use Sulu\Bundle\AutomationBundle\TaskHandler\TaskHandlerConfiguration;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents a test-handler.
 */
class TestHandler implements AutomationTaskHandlerInterface
{
    const TITLE = 'sulu_automation.test_handler';

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver)
    {
        return $optionsResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entityClass)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return TaskHandlerConfiguration::create(self::TITLE);
    }

    /**
     * {@inheritdoc}
     */
    public function handle($workload)
    {
        // do nothing
    }
}
