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

namespace Sulu\Notifier\Tests\Unit\Infrastructure\Sulu\Activity;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Exception\ResourceViewNotFoundException;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Notifier\Infrastructure\Sulu\Activity\DomainEventNotificationFactory;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DomainEventNotificationFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private ObjectProphecy $translator;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    /**
     * @param ObjectProphecy<ResourceViewUrlGeneratorInterface>|null $resourceViewUrlGenerator
     */
    private function createFactory(?ObjectProphecy $resourceViewUrlGenerator = null): DomainEventNotificationFactory
    {
        /** @var ResourceViewUrlGeneratorInterface|null $revealedResourceViewUrlGenerator */
        $revealedResourceViewUrlGenerator = $resourceViewUrlGenerator?->reveal();

        return new DomainEventNotificationFactory(
            $this->translator->reveal(),
            'en',
            $revealedResourceViewUrlGenerator,
        );
    }

    public function testSupportsDomainEvent(): void
    {
        $factory = $this->createFactory();

        self::assertTrue($factory->supports($this->prophesize(DomainEvent::class)->reveal()));
        self::assertFalse($factory->supports(new \stdClass()));
    }

    public function testCreateThrowsForNonDomainEvent(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createFactory()->create(new \stdClass(), ['chat/slack']);
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

    public function testCreatePassesThroughUntranslatedKeyWhenTranslationMissing(): void
    {
        // Symfony's translator returns the message id itself when no translation exists
        // (no strict mode configured) -- this factory no longer special-cases that.
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('unknown');
        $event->getEventType()->willReturn('frobnicated');
        $event->getUser()->willReturn(null);
        $event->getResourceTitle()->willReturn(null);
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => '', '{resourceLocale}' => ''];

        $this->translator->trans('sulu_notifier.subject.unknown.frobnicated', $params, 'admin', 'en')
            ->willReturn('sulu_notifier.subject.unknown.frobnicated');
        $this->translator->trans('sulu_activity.description.unknown.frobnicated', $params, 'admin', 'en')
            ->willReturn('sulu_activity.description.unknown.frobnicated');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('sulu_notifier.subject.unknown.frobnicated', $notification->getSubject());
        self::assertSame('sulu_activity.description.unknown.frobnicated', $notification->getContent());
    }

    public function testCreateEscapesChatMarkupInUserControlledValues(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getEventType()->willReturn('modified');
        $event->getResourceTitle()->willReturn('<!channel> & <https://evil.example|click>');
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn(['note' => '<b>hi</b>']);
        $event->getUser()->willReturn(null);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = [
            '{userFullName}' => 'Someone',
            '{resourceTitle}' => '&lt;!channel&gt; &amp; &lt;https://evil.example|click&gt;',
            '{resourceLocale}' => '',
            '{context_note}' => '&lt;b&gt;hi&lt;/b&gt;',
        ];

        $this->translator->trans('sulu_notifier.subject.pages.modified', $params, 'admin', 'en')
            ->willReturn('Page modified');
        $this->translator->trans('sulu_activity.description.pages.modified', $params, 'admin', 'en')
            ->willReturn('modified');

        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('Page modified', $notification->getSubject());
    }

    public function testCreateAppendsDeepLinkWhenResolvable(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getResourceId()->willReturn('3');
        $event->getResourceWebspaceKey()->willReturn('sulu');
        $event->getEventType()->willReturn('modified');
        $event->getResourceTitle()->willReturn('My page');
        $event->getResourceLocale()->willReturn('de');
        $event->getEventContext()->willReturn([]);
        $event->getUser()->willReturn(null);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => 'My page', '{resourceLocale}' => 'de'];

        $this->translator->trans('sulu_notifier.subject.pages.modified', $params, 'admin', 'en')
            ->willReturn('Page modified');
        $this->translator->trans('sulu_activity.description.pages.modified', $params, 'admin', 'en')
            ->willReturn('Someone modified the page "My page"');

        $resourceViewUrlGenerator = $this->prophesize(ResourceViewUrlGeneratorInterface::class);
        $resourceViewUrlGenerator->generate(
            'pages',
            'detail',
            ['id' => '3', 'webspace' => 'sulu', 'locale' => 'de'],
            UrlGeneratorInterface::ABSOLUTE_URL,
        )->willReturn('https://example.org/admin/#/webspaces/sulu/pages/de/3/details');

        $notification = $this->createFactory($resourceViewUrlGenerator)->create($event->reveal(), ['chat/slack']);

        self::assertSame(
            'Someone modified the page "My page"' . "\n\n" . 'https://example.org/admin/#/webspaces/sulu/pages/de/3/details',
            $notification->getContent(),
        );
    }

    public function testCreateSkipsLinkForRemovedEvent(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getEventType()->willReturn('removed');
        $event->getResourceTitle()->willReturn('My page');
        $event->getResourceLocale()->willReturn('de');
        $event->getEventContext()->willReturn([]);
        $event->getUser()->willReturn(null);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => 'My page', '{resourceLocale}' => 'de'];

        $this->translator->trans('sulu_notifier.subject.pages.removed', $params, 'admin', 'en')
            ->willReturn('Page removed');
        $this->translator->trans('sulu_activity.description.pages.removed', $params, 'admin', 'en')
            ->willReturn('Someone removed the page "My page"');

        $resourceViewUrlGenerator = $this->prophesize(ResourceViewUrlGeneratorInterface::class);
        $resourceViewUrlGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $notification = $this->createFactory($resourceViewUrlGenerator)->create($event->reveal(), ['chat/slack']);

        self::assertSame('Someone removed the page "My page"', $notification->getContent());
    }

    public function testCreateOmitsLinkWhenResourceViewNotConfigured(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getResourceId()->willReturn('3');
        $event->getResourceWebspaceKey()->willReturn(null);
        $event->getEventType()->willReturn('modified');
        $event->getResourceTitle()->willReturn('My page');
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);
        $event->getUser()->willReturn(null);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => 'My page', '{resourceLocale}' => ''];

        $this->translator->trans('sulu_notifier.subject.pages.modified', $params, 'admin', 'en')
            ->willReturn('Page modified');
        $this->translator->trans('sulu_activity.description.pages.modified', $params, 'admin', 'en')
            ->willReturn('Someone modified the page "My page"');

        $resourceViewUrlGenerator = $this->prophesize(ResourceViewUrlGeneratorInterface::class);
        $resourceViewUrlGenerator->generate('pages', 'detail', ['id' => '3'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willThrow(new ResourceViewNotFoundException('pages', 'detail'));

        $notification = $this->createFactory($resourceViewUrlGenerator)->create($event->reveal(), ['chat/slack']);

        self::assertSame('Someone modified the page "My page"', $notification->getContent());
    }

    public function testCreateStringifiesNonScalarContextValuesSafely(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getEventType()->willReturn('modified');
        $event->getResourceTitle()->willReturn(null);
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([
            'list' => ['a', 'b'],
            'object' => new \stdClass(),
        ]);
        $event->getUser()->willReturn(null);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = [
            '{userFullName}' => 'Someone',
            '{resourceTitle}' => '',
            '{resourceLocale}' => '',
            '{context_list}' => '["a","b"]',
            '{context_object}' => 'stdClass',
        ];

        $this->translator->trans('sulu_notifier.subject.pages.modified', $params, 'admin', 'en')
            ->willReturn('Page modified');
        $this->translator->trans('sulu_activity.description.pages.modified', $params, 'admin', 'en')
            ->willReturn('modified');

        // Prophecy fails the test if trans() is called with different (unstringified /
        // uncaught-throwing) parameters than expected above.
        $notification = $this->createFactory()->create($event->reveal(), ['chat/slack']);

        self::assertSame('Page modified', $notification->getSubject());
    }
}
