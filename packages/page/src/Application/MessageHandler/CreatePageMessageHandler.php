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
use Sulu\Page\Application\Mapper\PageMapperInterface;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Domain\Event\PageCreatedEvent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create a PageMapper to extend this Handler.
 */
final class CreatePageMessageHandler
{
    public const HOMEPAGE_PARENT_ID = 'homepage';

    /**
     * @param iterable<PageMapperInterface> $pageMappers
     */
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private iterable $pageMappers,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(CreatePageMessage $message): PageInterface
    {
        $data = $message->getData();
        /** @var string $locale */
        $locale = $data['locale'];
        $page = $this->pageRepository->createNew($message->getUuid());
        $page->setWebspaceKey($message->getWebspaceKey());
        $page = $this->setParent($message->getParentId(), $page);

        foreach ($this->pageMappers as $pageMapper) {
            $pageMapper->mapPageData($page, $data);
        }

        $this->pageRepository->add($page);

        $this->domainEventCollector->collect(new PageCreatedEvent($page, $locale, $data));

        return $page;
    }

    private function setParent(string $parentId, PageInterface $page): PageInterface
    {
        // only the homepage is allowed to not have a parent
        if (self::HOMEPAGE_PARENT_ID !== $parentId) {
            $parent = $this->pageRepository->getOneBy(['uuid' => $parentId]);
            $page->setParent($parent);
        }

        return $page;
    }
}
