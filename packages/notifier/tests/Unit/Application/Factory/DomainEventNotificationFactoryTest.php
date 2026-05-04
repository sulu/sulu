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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Notifier\Application\Factory\DomainEventNotificationFactory;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DomainEventNotificationFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    /**
     * @var ObjectProphecy<MessageCatalogueInterface>
     */
    private $catalogue;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->catalogue = $this->prophesize(MessageCatalogueInterface::class);

        $this->translator->willImplement(TranslatorBagInterface::class);
        $this->translator->getCatalogue('en')->willReturn($this->catalogue->reveal());
    }

    public function testSupportsDomainEvent(): void
    {
        $factory = $this->createFactory();

        self::assertTrue($factory->supports($this->prophesize(DomainEvent::class)->reveal()));
        self::assertFalse($factory->supports(new \stdClass()));
    }

    public function testCreateRendersTranslatedSubjectAndContent(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getEventType()->willReturn('workflow_transition.unpublish');
        $event->getResourceTitle()->willReturn('A great song will win');
        $event->getResourceLocale()->willReturn('de');
        $event->getEventContext()->willReturn([]);

        $user = $this->prophesize(UserInterface::class);
        $user->getFullName()->willReturn('Adam Ministrator');
        $event->getUser()->willReturn($user->reveal());

        $this->catalogue->has('sulu_notifier.subject.pages.workflow_transition.unpublish', 'admin')
            ->willReturn(true);
        $this->catalogue->has('sulu_activity.description.pages.workflow_transition.unpublish', 'admin')
            ->willReturn(true);

        $params = ['{userFullName}' => 'Adam Ministrator', '{resourceTitle}' => 'A great song will win', '{resourceLocale}' => 'de'];

        $this->translator->trans(
            'sulu_notifier.subject.pages.workflow_transition.unpublish',
            $params,
            'admin',
            'en',
        )->willReturn('Page unpublished');

        $this->translator->trans(
            'sulu_activity.description.pages.workflow_transition.unpublish',
            $params,
            'admin',
            'en',
        )->willReturn('Adam Ministrator has unpublished the page "A great song will win"');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('Page unpublished', $notification->getSubject());
        self::assertSame(
            'Adam Ministrator has unpublished the page "A great song will win"',
            $notification->getContent(),
        );
        self::assertSame(['chat/slack'], $notification->getChannels(new NoRecipient()));
    }

    public function testCreatePassesContextParameters(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getEventType()->willReturn('translation_copied');
        $event->getResourceTitle()->willReturn('My page');
        $event->getResourceLocale()->willReturn('de');
        $event->getEventContext()->willReturn(['sourceLocale' => 'en']);

        $user = $this->prophesize(UserInterface::class);
        $user->getFullName()->willReturn('Adam Ministrator');
        $event->getUser()->willReturn($user->reveal());

        $this->catalogue->has('sulu_notifier.subject.pages.translation_copied', 'admin')->willReturn(true);
        $this->catalogue->has('sulu_activity.description.pages.translation_copied', 'admin')->willReturn(true);

        $params = [
            '{userFullName}' => 'Adam Ministrator',
            '{resourceTitle}' => 'My page',
            '{resourceLocale}' => 'de',
            '{context_sourceLocale}' => 'en',
        ];

        $this->translator->trans('sulu_notifier.subject.pages.translation_copied', $params, 'admin', 'en')
            ->willReturn('Translation copied');
        $this->translator->trans('sulu_activity.description.pages.translation_copied', $params, 'admin', 'en')
            ->willReturn('Adam Ministrator has copied the "en" translation of the page "My page" into "de"');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('Translation copied', $notification->getSubject());
        self::assertSame(
            'Adam Ministrator has copied the "en" translation of the page "My page" into "de"',
            $notification->getContent(),
        );
    }

    public function testCreateUsesSomeoneWhenUserIsNull(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('tags');
        $event->getEventType()->willReturn('created');
        $event->getResourceTitle()->willReturn('news');
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);
        $event->getUser()->willReturn(null);

        $this->catalogue->has('sulu_notifier.subject.tags.created', 'admin')->willReturn(true);
        $this->catalogue->has('sulu_activity.description.tags.created', 'admin')->willReturn(true);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => 'news', '{resourceLocale}' => ''];

        $this->translator->trans('sulu_notifier.subject.tags.created', $params, 'admin', 'en')
            ->willReturn('Tag created');
        $this->translator->trans('sulu_activity.description.tags.created', $params, 'admin', 'en')
            ->willReturn('Someone created the tag "news"');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('Tag created', $notification->getSubject());
        self::assertSame('Someone created the tag "news"', $notification->getContent());
    }

    public function testCreateUsesEmptyStringWhenTitleIsNull(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('cache');
        $event->getEventType()->willReturn('cleared');
        $event->getResourceTitle()->willReturn(null);
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);
        $event->getUser()->willReturn(null);

        $this->catalogue->has('sulu_notifier.subject.cache.cleared', 'admin')->willReturn(true);
        $this->catalogue->has('sulu_activity.description.cache.cleared', 'admin')->willReturn(true);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => '', '{resourceLocale}' => ''];

        $this->translator->trans('sulu_notifier.subject.cache.cleared', $params, 'admin', 'en')
            ->willReturn('Cache cleared');
        $this->translator->trans('sulu_activity.description.cache.cleared', $params, 'admin', 'en')
            ->willReturn('Someone cleared the cache');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('Cache cleared', $notification->getSubject());
        self::assertSame('Someone cleared the cache', $notification->getContent());
    }

    public function testCreateUsesFallbackWhenSubjectKeyMissing(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('unknown');
        $event->getEventType()->willReturn('frobnicated');
        $event->getUser()->willReturn(null);
        $event->getResourceTitle()->willReturn(null);
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);

        $this->catalogue->has('sulu_notifier.subject.unknown.frobnicated', 'admin')->willReturn(false);
        $this->catalogue->has('sulu_activity.description.unknown.frobnicated', 'admin')->willReturn(true);

        $this->translator->trans('sulu_notifier.fallback.subject', Argument::any(), 'admin', 'en')
            ->willReturn('DomainEvent');
        $this->translator->trans('sulu_notifier.fallback.content', Argument::any(), 'admin', 'en')
            ->willReturn('Event DomainEvent occurred');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('DomainEvent', $notification->getSubject());
        self::assertSame('Event DomainEvent occurred', $notification->getContent());
    }

    public function testCreateUsesFallbackWhenContentKeyMissing(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getEventType()->willReturn('exotic');
        $event->getUser()->willReturn(null);
        $event->getResourceTitle()->willReturn(null);
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);

        $this->catalogue->has('sulu_notifier.subject.pages.exotic', 'admin')->willReturn(true);
        $this->catalogue->has('sulu_activity.description.pages.exotic', 'admin')->willReturn(false);

        $this->translator->trans('sulu_notifier.fallback.subject', Argument::any(), 'admin', 'en')
            ->willReturn('DomainEvent');
        $this->translator->trans('sulu_notifier.fallback.content', Argument::any(), 'admin', 'en')
            ->willReturn('Event DomainEvent occurred');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('DomainEvent', $notification->getSubject());
        self::assertSame('Event DomainEvent occurred', $notification->getContent());
    }

    private function createFactory(): DomainEventNotificationFactory
    {
        /** @var TranslatorInterface&TranslatorBagInterface $translator */
        $translator = $this->translator->reveal();

        return new DomainEventNotificationFactory($translator, 'en');
    }
}
