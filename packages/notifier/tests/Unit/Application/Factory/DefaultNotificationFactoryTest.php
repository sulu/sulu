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

namespace Sulu\Notifier\Tests\Unit\Application\Factory;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Notifier\Application\Factory\DefaultNotificationFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

class DefaultNotificationFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    public function testSupportsAnyEvent(): void
    {
        $factory = $this->createFactory();

        self::assertTrue($factory->supports(new \stdClass()));
        self::assertTrue($factory->supports(new \Exception()));
    }

    public function testCreateUsesFallbackTranslationKeys(): void
    {
        $event = new \stdClass();

        $this->translator->trans(
            'sulu_notifier.fallback.subject',
            ['%class%' => 'stdClass'],
            'admin',
            'en',
        )->willReturn('stdClass');

        $this->translator->trans(
            'sulu_notifier.fallback.content',
            ['%class%' => 'stdClass'],
            'admin',
            'en',
        )->willReturn('Event stdClass occurred');

        $factory = $this->createFactory();

        $notification = $factory->create($event, ['chat/slack', 'chat/discord']);

        self::assertSame('stdClass', $notification->getSubject());
        self::assertSame('Event stdClass occurred', $notification->getContent());
        self::assertSame(['chat/slack', 'chat/discord'], $notification->getChannels(new \Symfony\Component\Notifier\Recipient\NoRecipient()));
    }

    private function createFactory(): DefaultNotificationFactory
    {
        return new DefaultNotificationFactory($this->translator->reveal(), 'en');
    }
}
