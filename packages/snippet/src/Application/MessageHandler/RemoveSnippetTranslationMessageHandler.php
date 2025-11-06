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
use Sulu\Snippet\Application\Message\RemoveSnippetTranslationMessage;
use Sulu\Snippet\Domain\Event\SnippetTranslationRemovedEvent;
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
    ) {
    }

    public function __invoke(RemoveSnippetTranslationMessage $message): void
    {
        $snippet = $this->snippetRepository->getOneBy($message->getIdentifier());
        $locale = $message->getLocale();

        $dimensionContents = $snippet->getDimensionContents();

        foreach ($dimensionContents as $dimensionContent) {
            if ($dimensionContent->getLocale() === $locale) {
                $snippet->removeDimensionContent($dimensionContent);
                $this->snippetRepository->removeDimensionContent($dimensionContent);
            } elseif ($dimensionContent->getGhostLocale() === $locale) {
                $availableLocales = $dimensionContent->getAvailableLocales();
                $availableLocales = \array_values(\array_diff($availableLocales ?? [], [$locale]));

                if ([] === $availableLocales) {
                    $snippet->removeDimensionContent($dimensionContent);
                    $this->snippetRepository->removeDimensionContent($dimensionContent);

                    continue;
                }

                $dimensionContent->setGhostLocale($availableLocales[0]);
                $dimensionContent->removeAvailableLocale($locale);
            }
        }

        $this->domainEventCollector->collect(new SnippetTranslationRemovedEvent(
            $snippet,
            $locale
        ));
    }
}
