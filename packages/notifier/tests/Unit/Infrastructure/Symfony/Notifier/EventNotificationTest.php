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

namespace Sulu\Notifier\Tests\Unit\Infrastructure\Symfony\Notifier;

use PHPUnit\Framework\TestCase;
use Sulu\Notifier\Infrastructure\Symfony\Notifier\EventNotification;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Recipient\NoRecipient;

class EventNotificationTest extends TestCase
{
    private function createNotification(?string $link = 'https://example.org/admin/#/webspaces/sulu/pages/de/3/details'): EventNotification
    {
        return new EventNotification(
            subject: 'Page modified',
            description: 'Adam modified the page "Tom & Jerry"',
            link: $link,
            linkLabel: 'Open in Sulu',
            context: ['sulu', 'de'],
            channels: ['chat/slack'],
        );
    }

    public function testContentContainsDescriptionAndLink(): void
    {
        $notification = $this->createNotification();

        self::assertSame(
            'Adam modified the page "Tom & Jerry"' . "\n\n" . 'https://example.org/admin/#/webspaces/sulu/pages/de/3/details',
            $notification->getContent(),
        );
        self::assertSame(['chat/slack'], $notification->getChannels(new NoRecipient()));
    }

    public function testContentWithoutLink(): void
    {
        self::assertSame('Adam modified the page "Tom & Jerry"', $this->createNotification(null)->getContent());
    }

    public function testSlackMessageUsesBlocks(): void
    {
        $message = $this->createNotification()->asChatMessage(new NoRecipient(), 'slack');

        self::assertSame('Page modified', $message->getSubject());
        self::assertSame([
            ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => 'Page modified']],
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => 'Adam modified the page "Tom &amp; Jerry"']],
            ['type' => 'context', 'elements' => [['type' => 'mrkdwn', 'text' => 'sulu · de']]],
            ['type' => 'actions', 'elements' => [[
                'type' => 'button',
                'text' => ['type' => 'plain_text', 'text' => 'Open in Sulu'],
                'url' => 'https://example.org/admin/#/webspaces/sulu/pages/de/3/details',
                'style' => 'primary',
            ]]],
        ], $this->getSlackBlocks($message));
    }

    public function testSlackMessageOmitsEmptyContextAndMissingLink(): void
    {
        $message = (new EventNotification('Cache cleared', 'Adam cleared the cache'))
            ->asChatMessage(new NoRecipient(), 'slack');

        self::assertSame([
            ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => 'Cache cleared']],
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => 'Adam cleared the cache']],
        ], $this->getSlackBlocks($message));
    }

    public function testSlackMessageEscapesMarkup(): void
    {
        $notification = new EventNotification(
            subject: '<!channel> Page modified',
            description: 'Adam modified the page "<https://evil.example|click>"',
            context: ['<!here>'],
        );

        $message = $notification->asChatMessage(new NoRecipient(), 'slack');

        self::assertSame('&lt;!channel&gt; Page modified', $message->getSubject());
        self::assertSame([
            ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => '<!channel> Page modified']],
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => 'Adam modified the page "&lt;https://evil.example|click&gt;"']],
            ['type' => 'context', 'elements' => [['type' => 'mrkdwn', 'text' => '&lt;!here&gt;']]],
        ], $this->getSlackBlocks($message));
    }

    public function testSlackHeaderIsTruncatedToTheSlackLimit(): void
    {
        $message = (new EventNotification(\str_repeat('ä', 100), 'description'))
            ->asChatMessage(new NoRecipient(), 'slack');

        self::assertSame([
            ['type' => 'header', 'text' => ['type' => 'plain_text', 'text' => \str_repeat('ä', 73) . '…']],
            ['type' => 'section', 'text' => ['type' => 'mrkdwn', 'text' => 'description']],
        ], $this->getSlackBlocks($message));
    }

    public function testOtherTransportGetsPlainText(): void
    {
        $message = $this->createNotification()->asChatMessage(new NoRecipient(), 'discord');

        self::assertNull($message->getOptions());
        self::assertSame(
            'Page modified' . "\n" . 'Adam modified the page "Tom & Jerry"' . "\n\n" . 'https://example.org/admin/#/webspaces/sulu/pages/de/3/details',
            $message->getSubject(),
        );
    }

    public function testUnnamedTransportGetsEscapedPlainText(): void
    {
        $message = $this->createNotification(null)->asChatMessage(new NoRecipient());

        self::assertSame('Page modified' . "\n" . 'Adam modified the page "Tom &amp; Jerry"', $message->getSubject());
    }

    /**
     * Drops the "verbatim" flag, which only newer versions of symfony/slack-notifier emit.
     */
    private function getSlackBlocks(ChatMessage $message): mixed
    {
        $options = $message->getOptions();
        self::assertInstanceOf(SlackOptions::class, $options);

        return $this->removeVerbatim($options->toArray()['blocks']);
    }

    private function removeVerbatim(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        unset($value['verbatim']);

        return \array_map($this->removeVerbatim(...), $value);
    }
}
