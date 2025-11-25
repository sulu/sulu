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
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Page\Application\Message\RemovePageTranslationMessage;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemovePageTranslationMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TrashManagerInterface $trashManager = null,
    ) {
    }

    public function __invoke(RemovePageTranslationMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());
        $locale = $message->getLocale();

        /** @var string $resourceKey */
        $resourceKey = $page::RESOURCE_KEY;
        $this->trashManager?->store($resourceKey, $page, ['locale' => $locale]);

        $dimensionContents = $page->getDimensionContents();

        foreach ($dimensionContents as $dimensionContent) {
            if ($dimensionContent->getLocale() === $locale) {
                $page->removeDimensionContent($dimensionContent);
                $this->pageRepository->removeDimensionContent($dimensionContent);
                continue;
            }

            if ($dimensionContent->getGhostLocale() === $locale) {
                $this->handleGhostLocaleRemoval($dimensionContent, $page, $locale);
                continue;
            }

            if (null === $dimensionContent->getLocale()) {
                $dimensionContent->removeAvailableLocale($locale);
            }
        }

        $this->domainEventCollector->collect(new PageTranslationRemovedEvent(
            $page,
            $locale
        ));
    }

    private function handleGhostLocaleRemoval(
        PageDimensionContentInterface $dimensionContent,
        PageInterface $snippet,
        string $locale
    ): void {
        $availableLocales = $dimensionContent->getAvailableLocales();
        $availableLocales = \array_values(\array_diff($availableLocales ?? [], [$locale]));

        if (empty($availableLocales)) {
            $snippet->removeDimensionContent($dimensionContent);
            $this->pageRepository->removeDimensionContent($dimensionContent);

            return;
        }

        $dimensionContent->setGhostLocale($availableLocales[0]);
        $dimensionContent->removeAvailableLocale($locale);
    }
}
