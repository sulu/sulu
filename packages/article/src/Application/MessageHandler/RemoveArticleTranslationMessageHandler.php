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
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;

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
        private ?TrashManagerInterface $trashManager = null,
    ) {
    }

    public function __invoke(RemoveArticleTranslationMessage $message): void
    {
        $article = $this->articleRepository->getOneBy($message->getIdentifier());
        $locale = $message->getLocale();

        /** @var string $resourceKey */
        $resourceKey = $article::RESOURCE_KEY;
        $this->trashManager?->store($resourceKey, $article, ['locale' => $locale]);

        $dimensionContents = $article->getDimensionContents();

        foreach ($dimensionContents as $dimensionContent) {
            if ($dimensionContent->getLocale() === $locale) {
                $article->removeDimensionContent($dimensionContent);
                $this->articleRepository->removeDimensionContent($dimensionContent);
                continue;
            }

            if ($dimensionContent->getGhostLocale() === $locale) {
                $this->handleGhostLocaleRemoval($dimensionContent, $article, $locale);
                continue;
            }

            if (null === $dimensionContent->getLocale()) {
                $dimensionContent->removeAvailableLocale($locale);
            }
        }

        $this->domainEventCollector->collect(new ArticleTranslationRemovedEvent(
            $article,
            $locale
        ));
    }

    private function handleGhostLocaleRemoval(
        ArticleDimensionContentInterface $dimensionContent,
        ArticleInterface $article,
        string $locale
    ): void {
        $availableLocales = $dimensionContent->getAvailableLocales();
        $availableLocales = \array_values(\array_diff($availableLocales ?? [], [$locale]));

        if (empty($availableLocales)) {
            $article->removeDimensionContent($dimensionContent);
            $this->articleRepository->removeDimensionContent($dimensionContent);

            return;
        }

        $dimensionContent->setGhostLocale($availableLocales[0]);
        $dimensionContent->removeAvailableLocale($locale);
    }
}
