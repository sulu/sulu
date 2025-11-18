<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Application\MessageHandler;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Snippet\Application\Message\RemoveSnippetTranslationMessage;
use Sulu\Snippet\Domain\Event\SnippetTranslationRemovedEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveSnippetTranslationMessageHandler
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private ?TrashManagerInterface $trashManager = null,
    ) {
    }

    public function __invoke(RemoveSnippetTranslationMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());
        $locale = $message->getLocale();

        $this->trashManager?->store($snippet::RESOURCE_KEY, $snippet, ['locale' => $locale]);

        $dimensionContents = $snippet->getDimensionContents();

        foreach ($dimensionContents as $dimensionContent) {
            if ($dimensionContent->getLocale() === $locale) {
                $snippet->removeDimensionContent($dimensionContent);
                $this->snippetRepository->removeDimensionContent($dimensionContent);
                continue;
            }

            if ($dimensionContent->getGhostLocale() === $locale) {
                $this->handleGhostLocaleRemoval($dimensionContent, $snippet, $locale);
                continue;
            }

            if (null === $dimensionContent->getLocale()) {
                $dimensionContent->removeAvailableLocale($locale);
            }
        }

        $this->domainEventCollector->collect(new SnippetTranslationRemovedEvent(
            $snippet,
            $locale
        ));
    }

    private function handleGhostLocaleRemoval(
        SnippetDimensionContentInterface $dimensionContent,
        SnippetInterface $snippet,
        string $locale
    ): void {
        $availableLocales = $dimensionContent->getAvailableLocales();
        $availableLocales = \array_values(\array_diff($availableLocales ?? [], [$locale]));

        if (empty($availableLocales)) {
            $snippet->removeDimensionContent($dimensionContent);
            $this->snippetRepository->removeDimensionContent($dimensionContent);

            return;
        }

        $dimensionContent->setGhostLocale($availableLocales[0]);
        $dimensionContent->removeAvailableLocale($locale);
    }
}
