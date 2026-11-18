<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Webspace\Tests\Unit\Infrastructure\Sulu\HttpCache\EventSubscriber;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Webspace\Domain\Event\WebspaceSettingWorkflowTransitionAppliedEvent;
use Sulu\Webspace\Domain\Model\WebspaceSetting;
use Sulu\Webspace\Infrastructure\Sulu\HttpCache\EventSubscriber\WebspaceSettingCacheInvalidationSubscriber;

class WebspaceSettingCacheInvalidationSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CacheManagerInterface>
     */
    private ObjectProphecy $cacheManager;

    private WebspaceSettingCacheInvalidationSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->cacheManager = $this->prophesize(CacheManagerInterface::class);
        $this->subscriber = new WebspaceSettingCacheInvalidationSubscriber($this->cacheManager->reveal());
    }

    #[TestWith([WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH])]
    #[TestWith([WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH])]
    public function testInvalidatesTheReferenceOfTheWebspaceOnPublishAndUnpublish(string $transition): void
    {
        $this->subscriber->onWorkflowTransition($this->createEvent($transition));

        $this->cacheManager->invalidateReference('webspace_settings', 'sulu-io')->shouldHaveBeenCalledOnce();
    }

    #[TestWith([WorkflowInterface::WORKFLOW_TRANSITION_REMOVE_DRAFT])]
    #[TestWith([WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW])]
    public function testKeepsTheCacheForTransitionsWhichDoNotChangeTheLiveContent(string $transition): void
    {
        $this->subscriber->onWorkflowTransition($this->createEvent($transition));

        $this->cacheManager->invalidateReference(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testWithoutCacheManager(): void
    {
        $subscriber = new WebspaceSettingCacheInvalidationSubscriber(null);

        $subscriber->onWorkflowTransition($this->createEvent(WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH));

        $this->expectNotToPerformAssertions();
    }

    private function createEvent(string $transition): WebspaceSettingWorkflowTransitionAppliedEvent
    {
        return new WebspaceSettingWorkflowTransitionAppliedEvent(new WebspaceSetting('sulu-io'), $transition, 'en');
    }
}
