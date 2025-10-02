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

namespace Sulu\Page\Tests\Traits;

use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Domain\Model\Page;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

trait CreatePageTrait
{
    /**
     * @param array<string, array{ draft?: array<string, mixed>, live?: array<string, mixed> }> $dataSet
     */
    protected static function createPage(array $dataSet = [], string $webspaceKey = 'sulu-io'): Page
    {
        if (empty($dataSet)) {
            throw new \InvalidArgumentException('dataSet must contain at least one locale');
        }

        $messageBus = self::getContainer()->get('sulu_message_bus');

        // Process first locale with CreatePageMessage
        $firstLocale = \array_key_first($dataSet);
        $firstLocaleData = $dataSet[$firstLocale];

        // Use live data for initial creation if it exists, otherwise draft
        $initialData = $firstLocaleData['live'] ?? $firstLocaleData['draft'] ?? [];
        $initialData['locale'] = $firstLocale;

        // Create page
        $envelope = $messageBus->dispatch(
            new Envelope(
                new CreatePageMessage(
                    webspaceKey: $webspaceKey,
                    parentId: CreatePageMessageHandler::HOMEPAGE_PARENT_ID,
                    data: $initialData
                ),
                [new EnableFlushStamp()]
            )
        );

        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var Page $page */
        $page = $handledStamps[0]->getResult();

        // Handle first locale's live/draft stages
        $hasLive = isset($firstLocaleData['live']);
        $hasDraft = isset($firstLocaleData['draft']);

        if ($hasLive) {
            // Publish to create live dimension
            $messageBus->dispatch(
                new Envelope(
                    new ApplyWorkflowTransitionPageMessage(
                        identifier: ['uuid' => $page->getUuid()],
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
                        new ModifyPageMessage(
                            identifier: ['uuid' => $page->getUuid()],
                            data: $draftData
                        ),
                        [new EnableFlushStamp()]
                    )
                );
            }
        }

        // Process additional locales with ModifyPageMessage
        $remainingLocales = \array_slice($dataSet, 1, null, true);
        foreach ($remainingLocales as $locale => $localeData) {
            $modifyData = $localeData['live'] ?? $localeData['draft'] ?? [];
            $modifyData['locale'] = $locale;

            $messageBus->dispatch(
                new Envelope(
                    new ModifyPageMessage(
                        identifier: ['uuid' => $page->getUuid()],
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
                        new ApplyWorkflowTransitionPageMessage(
                            identifier: ['uuid' => $page->getUuid()],
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
                            new ModifyPageMessage(
                                identifier: ['uuid' => $page->getUuid()],
                                data: $draftData
                            ),
                            [new EnableFlushStamp()]
                        )
                    );
                }
            }
        }

        return $page;
    }
}
