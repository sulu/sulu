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

namespace Sulu\Snippet\Tests\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Snippet\Application\Message\ApplyWorkflowTransitionSnippetMessage;
use Sulu\Snippet\Application\Message\CreateSnippetMessage;
use Sulu\Snippet\Application\Message\ModifySnippetMessage;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetArea;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

trait CreateSnippetTrait
{
    /**
     * @param array<string, array{ draft?: array<string, mixed>, live?: array<string, mixed> }> $dataSet
     */
    protected static function createSnippet(array $dataSet = []): Snippet
    {
        if (empty($dataSet)) {
            throw new \InvalidArgumentException('dataSet must contain at least one locale');
        }

        $messageBus = self::getContainer()->get('sulu_message_bus');

        // Process first locale with CreateSnippetMessage
        $firstLocale = \array_key_first($dataSet);
        $firstLocaleData = $dataSet[$firstLocale];

        // Use live data for initial creation if it exists, otherwise draft
        $initialData = $firstLocaleData['live'] ?? $firstLocaleData['draft'] ?? [];
        $initialData['locale'] = $firstLocale;

        // Create snippet
        $envelope = $messageBus->dispatch(
            new Envelope(
                new CreateSnippetMessage(data: $initialData),
                [new EnableFlushStamp()]
            )
        );

        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var Snippet $snippet */
        $snippet = $handledStamps[0]->getResult();

        // Handle first locale's live/draft stages
        $hasLive = isset($firstLocaleData['live']);
        $hasDraft = isset($firstLocaleData['draft']);

        if ($hasLive) {
            // Publish to create live dimension
            $messageBus->dispatch(
                new Envelope(
                    new ApplyWorkflowTransitionSnippetMessage(
                        identifier: ['uuid' => $snippet->getUuid()],
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
                        new ModifySnippetMessage(
                            identifier: ['uuid' => $snippet->getUuid()],
                            data: $draftData
                        ),
                        [new EnableFlushStamp()]
                    )
                );
            }
        }

        // Process additional locales with ModifySnippetMessage
        $remainingLocales = \array_slice($dataSet, 1, null, true);
        foreach ($remainingLocales as $locale => $localeData) {
            $modifyData = $localeData['live'] ?? $localeData['draft'] ?? [];
            $modifyData['locale'] = $locale;

            $messageBus->dispatch(
                new Envelope(
                    new ModifySnippetMessage(
                        identifier: ['uuid' => $snippet->getUuid()],
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
                        new ApplyWorkflowTransitionSnippetMessage(
                            identifier: ['uuid' => $snippet->getUuid()],
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
                            new ModifySnippetMessage(
                                identifier: ['uuid' => $snippet->getUuid()],
                                data: $draftData
                            ),
                            [new EnableFlushStamp()]
                        )
                    );
                }
            }
        }

        return $snippet;
    }

    protected static function createSnippetArea(string $areaKey, string $webspaceKey, SnippetInterface $snippet): SnippetArea
    {
        $entityManager = static::getEntityManager();

        $snippetAreaRepository = static::getContainer()->get('sulu_snippet.snippet_area_repository');
        $existingSnippetArea = $snippetAreaRepository->findOneBy([
            'areaKey' => $areaKey,
            'webspaceKey' => $webspaceKey,
        ]);

        if ($existingSnippetArea instanceof SnippetArea) {
            $existingSnippetArea->setSnippet($snippet);

            return $existingSnippetArea;
        }

        $snippetArea = new SnippetArea($areaKey, $webspaceKey);
        $snippetArea->setSnippet($snippet);
        $entityManager->persist($snippetArea);

        return $snippetArea;
    }

    abstract protected static function getEntityManager(): EntityManagerInterface;
}
