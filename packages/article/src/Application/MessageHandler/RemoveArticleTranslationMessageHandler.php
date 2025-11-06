<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\MessageHandler;

use Sulu\Article\Application\Message\RemoveArticleTranslationMessage;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemoveArticleTranslationMessageHandler
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private DomainEventCollectorInterface $domainEventCollector,
    ) {
    }

    public function __invoke(RemoveArticleTranslationMessage $message): void
    {
        $article = $this->articleRepository->getOneBy($message->getIdentifier());
        $locale = $message->getLocale();

        $dimensionContents = $article->getDimensionContents();

        foreach ($dimensionContents as $dimensionContent) {
            if ($dimensionContent->getLocale() === $locale) {
                $article->removeDimensionContent($dimensionContent);
                $this->articleRepository->removeDimensionContent($dimensionContent);
            } elseif ($dimensionContent->getGhostLocale() === $locale) {
                $availableLocales = $dimensionContent->getAvailableLocales();
                $availableLocales = \array_values(\array_diff($availableLocales ?? [], [$locale]));

                if ([] === $availableLocales) {
                    $article->removeDimensionContent($dimensionContent);
                    $this->articleRepository->removeDimensionContent($dimensionContent);

                    continue;
                }

                $dimensionContent->setGhostLocale($availableLocales[0]);
                $dimensionContent->removeAvailableLocale($locale);
            }
        }

        $this->domainEventCollector->collect(new ArticleTranslationRemovedEvent(
            $article,
            $locale
        ));
    }
}
