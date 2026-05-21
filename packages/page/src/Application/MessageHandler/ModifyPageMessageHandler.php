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
use Sulu\Content\Application\ContentHash\ContentHashChecker;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Application\Mapper\PageMapperInterface;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Domain\Event\PageModifiedEvent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create a PageMapper to extend this Handler.
 */
final class ModifyPageMessageHandler
{
    /**
     * @param iterable<PageMapperInterface> $pageMappers
     */
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        /** @var iterable<PageMapperInterface> */
        private iterable $pageMappers,
        private DomainEventCollectorInterface $domainEventCollector,
        private ContentHashChecker $contentHashChecker,
    ) {
    }

    public function __invoke(ModifyPageMessage $message): PageInterface
    {
        $identifier = $message->getIdentifier();
        $data = $message->getData();
        /** @var string $locale */
        $locale = $data['locale'];

        $page = $this->pageRepository->getOneBy(
            [
                ...$identifier,
                'locale' => $locale,
            ],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ]
        );

        $this->contentHashChecker->checkHash(
            $data,
            $page,
            ['locale' => $locale, 'stage' => DimensionContentInterface::STAGE_DRAFT],
            $page->getId(),
        );

        foreach ($this->pageMappers as $pageMapper) {
            $pageMapper->mapPageData($page, $data);
        }

        $this->domainEventCollector->collect(new PageModifiedEvent($page, $locale, $data));

        return $page;
    }
}
