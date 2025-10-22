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
use Sulu\Page\Application\Message\RemovePageTranslationMessage;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemovePageTranslationMessageHandler
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(RemovePageTranslationMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());
        $locale = $message->getLocale();

        $dimensionContents = $page->getDimensionContents();

        foreach ($dimensionContents as $dimensionContent) {
            if ($dimensionContent->getLocale() === $locale || $dimensionContent->getGhostLocale() === $locale) {
                $page->removeDimensionContent($dimensionContent);
                $this->pageRepository->removeDimensionContent($dimensionContent);
            } elseif ($dimensionContent->getGhostLocale() === $locale) {
                /** @var string[] $availableLocales */
                $availableLocales = $dimensionContent->getAvailableLocales();
                $availableLocales = \array_values(\array_diff($availableLocales, [$locale]));

                if ([] === $availableLocales) {
                    $this->pageRepository->removeDimensionContent($dimensionContent);

                    continue;
                }

                $dimensionContent->setGhostLocale($availableLocales[0]);
                $dimensionContent->removeAvailableLocale($locale);
            }
        }

        $this->domainEventCollector->collect(new PageTranslationRemovedEvent(
            $page,
            $locale
        ));
    }
}
