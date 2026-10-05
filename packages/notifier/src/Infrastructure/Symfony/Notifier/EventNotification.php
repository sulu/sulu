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

namespace Sulu\Notifier\Infrastructure\Symfony\Notifier;

use Symfony\Component\Notifier\Bridge\Slack\Block\SlackActionsBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackContextBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackHeaderBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackSectionBlock;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

/**
 * Renders a notification as Slack blocks on the "slack" transport and as plain text
 * with description and link on every other chat transport.
 */
final class EventNotification extends Notification implements ChatNotificationInterface
{
    private const SLACK_HEADER_LIMIT = 150;

    /**
     * @param list<string> $context short facts shown below the description, e.g. webspace and locale
     * @param list<string> $channels
     */
    public function __construct(
        string $subject,
        private readonly string $description,
        private readonly ?string $link = null,
        private readonly string $linkLabel = 'Open',
        private readonly array $context = [],
        array $channels = [],
    ) {
        parent::__construct($subject, $channels);

        $this->content(null !== $link ? $description . "\n\n" . $link : $description);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @return list<string>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function asChatMessage(RecipientInterface $recipient, ?string $transport = null): ChatMessage
    {
        if ('slack' === $transport && \class_exists(SlackOptions::class)) {
            return $this->asSlackMessage();
        }

        // The transport name is the project's choice and does not tell which service is behind it,
        // so the text is escaped for every transport, because Slack parses plain text as markup too.
        $text = self::escapeForChat($this->getSubject()) . "\n" . self::escapeForChat($this->description);

        return new ChatMessage(null !== $this->link ? $text . "\n\n" . $this->link : $text);
    }

    private function asSlackMessage(): ChatMessage
    {
        $options = (new SlackOptions())
            ->block(new SlackHeaderBlock(self::truncate($this->getSubject(), self::SLACK_HEADER_LIMIT)))
            ->block((new SlackSectionBlock())->text(self::escapeForChat($this->description)));

        if ([] !== $this->context) {
            $options->block((new SlackContextBlock())->text(self::escapeForChat(\implode(' · ', $this->context))));
        }

        if (null !== $this->link) {
            $options->block((new SlackActionsBlock())->button($this->linkLabel, $this->link, 'primary'));
        }

        return new ChatMessage(self::escapeForChat($this->getSubject()), $options);
    }

    /**
     * Resource titles, user names and event context are user-controlled, so they must not
     * be able to inject links, mentions or channel pings.
     *
     * @see https://api.slack.com/reference/surfaces/formatting#escaping
     */
    private static function escapeForChat(string $value): string
    {
        return \str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
    }

    /**
     * SlackHeaderBlock measures its limit with strlen() and throws above it, so this cuts by bytes
     * (at a character boundary) although Slack itself counts characters.
     */
    private static function truncate(string $value, int $bytes): string
    {
        if (\strlen($value) <= $bytes) {
            return $value;
        }

        return \mb_strcut($value, 0, $bytes - \strlen('…')) . '…';
    }
}
