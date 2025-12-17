<?php

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
use Sulu\Page\Application\Message\RestorePageVersionMessage;
use Sulu\Page\Domain\Event\PageVersionRestoredEvent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
class RestorePageVersionMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private ContentCopierInterface $contentCopier,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(RestorePageVersionMessage $message): PageInterface
    {
        $options = $message->getOptions();
        $stage = $options['stage'] ?? DimensionContentInterface::STAGE_DRAFT;

        $page = $this->pageRepository->getOneBy(
            $message->getPageIdentifier(),
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true],
                    'dimensionAttributes' => [
                        'locale' => $message->getLocale(),
                        'stage' => $stage,
                        'versions' => [$message->getVersion(), DimensionContentInterface::CURRENT_VERSION],
                    ],
                ],
            ]
        );

        $dimensionContent = $this->contentCopier->copy(
            $page,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => $message->getVersion(),
            ],
            $page,
            [
                'stage' => $stage,
                'locale' => $message->getLocale(),
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            [
                'ignoredAttributes' => ['url'],
            ]
        );

        $this->domainEventCollector->collect(new PageVersionRestoredEvent($page, $message->getLocale(), $message->getVersion()));

        return $dimensionContent->getResource();
    }
}
