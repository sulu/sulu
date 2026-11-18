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

namespace Sulu\Webspace\Application\MessageHandler;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Webspace\Application\Message\CopyLocaleWebspaceSettingMessage;
use Sulu\Webspace\Domain\Event\WebspaceSettingTranslationCopiedEvent;
use Sulu\Webspace\Domain\Model\WebspaceSettingInterface;
use Sulu\Webspace\Domain\Repository\WebspaceSettingRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class CopyLocaleWebspaceSettingMessageHandler
{
    public function __construct(
        private WebspaceSettingRepositoryInterface $webspaceSettingRepository,
        private ContentCopierInterface $contentCopier,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(CopyLocaleWebspaceSettingMessage $message): WebspaceSettingInterface
    {
        $webspaceSetting = $this->webspaceSettingRepository->getOneBy(
            ['webspaceKey' => $message->getWebspaceKey()],
            [
                WebspaceSettingRepositoryInterface::SELECT_WEBSPACE_SETTING_CONTENT => [
                    'selects' => [],
                    'dimensionAttributes' => [
                        'locale' => [$message->getSourceLocale(), $message->getTargetLocale()],
                        'stage' => DimensionContentInterface::STAGE_DRAFT,
                    ],
                ],
            ],
        );

        $this->contentCopier->copy(
            $webspaceSetting,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getSourceLocale(),
            ],
            $webspaceSetting,
            [
                'stage' => DimensionContentInterface::STAGE_DRAFT,
                'locale' => $message->getTargetLocale(),
            ],
        );

        $this->domainEventCollector->collect(new WebspaceSettingTranslationCopiedEvent(
            $webspaceSetting,
            $message->getTargetLocale(),
            $message->getSourceLocale(),
        ));

        return $webspaceSetting;
    }
}
