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

namespace Sulu\Article\Tests\Traits;

use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Application\Message\ModifyArticleMessage;
use Sulu\Article\Domain\Model\Article;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

trait CreateArticleTrait
{
    /**
     * @param array<string, array{ draft?: array<string, mixed>, live?: array<string, mixed> }> $dataSet
     */
    protected static function createArticle(array $dataSet = []): Article
    {
        if (empty($dataSet)) {
            throw new \InvalidArgumentException('dataSet must contain at least one locale');
        }

        $messageBus = self::getContainer()->get('sulu_message_bus');

        // Process first locale with CreateArticleMessage
        $firstLocale = \array_key_first($dataSet);
        $firstLocaleData = $dataSet[$firstLocale];

        // Use live data for initial creation if it exists, otherwise draft
        $initialData = $firstLocaleData['live'] ?? $firstLocaleData['draft'] ?? [];
        $initialData['locale'] = $firstLocale;

        // Create article
        $envelope = $messageBus->dispatch(
            new Envelope(
                new CreateArticleMessage(data: $initialData),
                [new EnableFlushStamp()]
            )
        );

        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var Article $article */
        $article = $handledStamps[0]->getResult();

        // Handle first locale's live/draft stages
        $hasLive = isset($firstLocaleData['live']);
        $hasDraft = isset($firstLocaleData['draft']);

        if ($hasLive) {
            // Publish to create live dimension
            $messageBus->dispatch(
                new Envelope(
                    new ApplyWorkflowTransitionArticleMessage(
                        identifier: ['uuid' => $article->getUuid()],
                        locale: $firstLocale,
                        transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
                    ),
                    [new EnableFlushStamp()]
                )
            );

            // If both draft and live exist, modify draft with different content
            if ($hasDraft) {
                $draftData = $firstLocaleData['draft'];
                $draftData['locale'] = $firstLocale;

                $messageBus->dispatch(
                    new Envelope(
                        new ModifyArticleMessage(
                            identifier: ['uuid' => $article->getUuid()],
                            data: $draftData
                        ),
                        [new EnableFlushStamp()]
                    )
                );
            }
        }

        // Process additional locales with ModifyArticleMessage
        $remainingLocales = \array_slice($dataSet, 1, null, true);
        foreach ($remainingLocales as $locale => $localeData) {
            $modifyData = $localeData['live'] ?? $localeData['draft'] ?? [];
            $modifyData['locale'] = $locale;

            $messageBus->dispatch(
                new Envelope(
                    new ModifyArticleMessage(
                        identifier: ['uuid' => $article->getUuid()],
                        data: $modifyData
                    ),
                    [new EnableFlushStamp()]
                )
            );

            // Handle this locale's live/draft stages
            $hasLive = isset($localeData['live']);
            $hasDraft = isset($localeData['draft']);

            if ($hasLive) {
                // Publish to create live dimension
                $messageBus->dispatch(
                    new Envelope(
                        new ApplyWorkflowTransitionArticleMessage(
                            identifier: ['uuid' => $article->getUuid()],
                            locale: $locale,
                            transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
                        ),
                        [new EnableFlushStamp()]
                    )
                );

                // If both draft and live exist, modify draft with different content
                if ($hasDraft) {
                    $draftData = $localeData['draft'];
                    $draftData['locale'] = $locale;

                    $messageBus->dispatch(
                        new Envelope(
                            new ModifyArticleMessage(
                                identifier: ['uuid' => $article->getUuid()],
                                data: $draftData
                            ),
                            [new EnableFlushStamp()]
                        )
                    );
                }
            }
        }

        return $article;
    }
}
