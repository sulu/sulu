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

namespace Sulu\Page\Application\MessageHandler;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionWebspaceSettingMessage;
use Sulu\Page\Domain\Event\WebspaceSettingWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Domain\Repository\WebspaceSettingRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class ApplyWorkflowTransitionWebspaceSettingMessageHandler
{
    public function __construct(
        private WebspaceSettingRepositoryInterface $webspaceSettingRepository,
        private ContentWorkflowInterface $contentWorkflow,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(ApplyWorkflowTransitionWebspaceSettingMessage $message): WebspaceSettingInterface
    {
        $webspaceSetting = $this->webspaceSettingRepository->getOneBy(
            ['webspaceKey' => $message->getWebspaceKey()],
            [
                WebspaceSettingRepositoryInterface::SELECT_WEBSPACE_SETTING_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $message->getLocale(),
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ],
        );

        $this->contentWorkflow->apply(
            $webspaceSetting,
            ['locale' => $message->getLocale()],
            $message->getTransitionName(),
        );

        $this->domainEventCollector->collect(new WebspaceSettingWorkflowTransitionAppliedEvent(
            $webspaceSetting,
            $message->getTransitionName(),
            $message->getLocale(),
        ));

        return $webspaceSetting;
    }
}
