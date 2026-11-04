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
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Application\Message\RestoreWebspaceSettingVersionMessage;
use Sulu\Page\Domain\Event\WebspaceSettingVersionRestoredEvent;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Domain\Repository\WebspaceSettingRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RestoreWebspaceSettingVersionMessageHandler
{
    public function __construct(
        private WebspaceSettingRepositoryInterface $webspaceSettingRepository,
        private ContentCopierInterface $contentCopier,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(RestoreWebspaceSettingVersionMessage $message): WebspaceSettingInterface
    {
        $stage = $message->getStage();

        $webspaceSetting = $this->webspaceSettingRepository->getOneBy(
            ['webspaceKey' => $message->getWebspaceKey()],
            [
                WebspaceSettingRepositoryInterface::SELECT_WEBSPACE_SETTING_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true],
                    'dimensionAttributes' => [
                        'locale' => $message->getLocale(),
                        'stage' => $stage,
                        'version' => [$message->getVersion(), DimensionContentInterface::CURRENT_VERSION],
                    ],
                ],
            ],
        );

        $this->contentCopier->copy(
            $webspaceSetting,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => $message->getVersion(),
            ],
            $webspaceSetting,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
        );

        $this->domainEventCollector->collect(new WebspaceSettingVersionRestoredEvent(
            $webspaceSetting,
            $message->getLocale(),
            $message->getVersion(),
        ));

        return $webspaceSetting;
    }
}
