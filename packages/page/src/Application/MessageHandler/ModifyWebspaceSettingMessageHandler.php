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
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentHash\ContentHashChecker;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Application\Mapper\WebspaceSettingMapperInterface;
use Sulu\Page\Application\Message\ModifyWebspaceSettingMessage;
use Sulu\Page\Domain\Event\WebspaceSettingCreatedEvent;
use Sulu\Page\Domain\Event\WebspaceSettingModifiedEvent;
use Sulu\Page\Domain\Exception\WebspaceSettingNotFoundException;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Domain\Repository\WebspaceSettingRepositoryInterface;

/**
 * Modifies the settings of a webspace, the settings are created with the first modification.
 *
 * @internal This class should not be instantiated by a project.
 *           Create a WebspaceSettingMapper to extend this Handler.
 */
final class ModifyWebspaceSettingMessageHandler
{
    /**
     * @param iterable<WebspaceSettingMapperInterface> $webspaceSettingMappers
     */
    public function __construct(
        private WebspaceSettingRepositoryInterface $webspaceSettingRepository,
        private WebspaceManagerInterface $webspaceManager,
        private iterable $webspaceSettingMappers,
        private DomainEventCollectorInterface $domainEventCollector,
        private ContentHashChecker $contentHashChecker,
    ) {
    }

    /**
     * @throws WebspaceSettingNotFoundException when the webspace does not define a settings form
     */
    public function __invoke(ModifyWebspaceSettingMessage $message): WebspaceSettingInterface
    {
        $data = $message->getData();
        /** @var string $locale */
        $locale = $data['locale'];
        $webspaceKey = $message->getWebspaceKey();

        $webspaceSettingsForm = $this->webspaceManager->findWebspaceByKey($webspaceKey)?->getWebspaceSettingsForm();
        if (null === $webspaceSettingsForm) {
            throw new WebspaceSettingNotFoundException($webspaceKey);
        }

        $data['template'] = $webspaceSettingsForm;

        $webspaceSetting = $this->webspaceSettingRepository->findOneBy(
            ['webspaceKey' => $webspaceKey],
            [
                WebspaceSettingRepositoryInterface::SELECT_WEBSPACE_SETTING_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ],
        );

        $created = null === $webspaceSetting;
        if (null === $webspaceSetting) {
            $webspaceSetting = $this->webspaceSettingRepository->createNew($webspaceKey);
            $this->webspaceSettingRepository->add($webspaceSetting);
        } else {
            $this->contentHashChecker->checkHash(
                $data,
                $webspaceSetting,
                ['locale' => $locale, 'stage' => DimensionContentInterface::STAGE_DRAFT],
                $webspaceSetting->getId(),
            );
        }

        foreach ($this->webspaceSettingMappers as $webspaceSettingMapper) {
            $webspaceSettingMapper->mapWebspaceSettingData($webspaceSetting, $data);
        }

        $this->domainEventCollector->collect($created
            ? new WebspaceSettingCreatedEvent($webspaceSetting, $locale, $data)
            : new WebspaceSettingModifiedEvent($webspaceSetting, $locale, $data));

        return $webspaceSetting;
    }
}
