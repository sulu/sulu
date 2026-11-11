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
use Sulu\Page\Application\Message\ApplyWorkflowTransitionWebspaceSettingMessage;
use Sulu\Page\Application\Message\ModifyWebspaceSettingMessage;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

trait CreateWebspaceSettingTrait
{
    /**
     * @param array<string, array{draft?: array<string, mixed>, live?: array<string, mixed>}> $dataSet
     */
    protected static function createWebspaceSetting(string $webspaceKey, array $dataSet): WebspaceSettingInterface
    {
        $messageBus = self::getContainer()->get('sulu_message_bus');

        $webspaceSetting = null;
        foreach ($dataSet as $locale => $localeData) {
            $data = ['locale' => $locale, ...($localeData['live'] ?? $localeData['draft'] ?? [])];

            /** @var HandledStamp $handledStamp */
            $handledStamp = $messageBus
                ->dispatch(new Envelope(new ModifyWebspaceSettingMessage($webspaceKey, $data), [new EnableFlushStamp()]))
                ->last(HandledStamp::class);
            /** @var WebspaceSettingInterface $webspaceSetting */
            $webspaceSetting = $handledStamp->getResult();

            if (!isset($localeData['live'])) {
                continue;
            }

            $messageBus->dispatch(new Envelope(
                new ApplyWorkflowTransitionWebspaceSettingMessage(
                    $webspaceKey,
                    $locale,
                    WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ));

            if (isset($localeData['draft'])) {
                $messageBus->dispatch(new Envelope(
                    new ModifyWebspaceSettingMessage(
                        $webspaceKey,
                        ['locale' => $locale, ...$localeData['draft']],
                    ),
                    [new EnableFlushStamp()],
                ));
            }
        }

        if (null === $webspaceSetting) {
            throw new \InvalidArgumentException('The data set must contain at least one locale.');
        }

        return $webspaceSetting;
    }
}
