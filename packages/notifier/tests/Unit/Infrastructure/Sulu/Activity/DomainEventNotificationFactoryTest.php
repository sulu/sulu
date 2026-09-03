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
use Psr\Log\LoggerInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Notifier\Infrastructure\Sulu\Activity\DomainEventNotificationFactory;
use Symfony\Component\HttpFoundation\RequestStack;
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

    /**
     * @var ObjectProphecy<UrlGeneratorInterface>
     */
    private ObjectProphecy $urlGenerator;

    /**
     * @var ObjectProphecy<ViewRegistry>
     */
    private ObjectProphecy $viewRegistry;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private ObjectProphecy $requestStack;

    /**
     * @var ObjectProphecy<LoggerInterface>
     */
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->viewRegistry = $this->prophesize(ViewRegistry::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @param array<string, array{views?: array<string, string>}> $resources
     */
    private function createFactory(bool $withResourceViewUrlGenerator = false, array $resources = []): DomainEventNotificationFactory
    {
        $resourceViewUrlGenerator = null;

        if ($withResourceViewUrlGenerator) {
            $viewUrlGenerator = new ViewUrlGenerator(
                $this->urlGenerator->reveal(),
                $this->viewRegistry->reveal(),
                $this->requestStack->reveal(),
            );
            $resourceViewUrlGenerator = new ResourceViewUrlGenerator($viewUrlGenerator, $resources);
        }

        return new DomainEventNotificationFactory(
            $this->translator->reveal(),
            'en',
            $resourceViewUrlGenerator,
            $this->logger->reveal(),
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

        $view = new View('sulu_page.page_edit_form.detail', '/webspaces/:webspace/pages/:locale/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_page.page_edit_form.detail')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/admin/');

        $resources = ['pages' => ['views' => ['detail' => 'sulu_page.page_edit_form.detail']]];
        $notification = $this->createFactory(true, $resources)->create($event->reveal(), ['chat/slack']);

        self::assertSame(
            'Someone modified the page "My page"' . "\n\n" . 'https://example.org/admin/#/webspaces/sulu/pages/de/3/details',
            $notification->getContent(),
        );
    }

    public function testCreateIncludesLinkForSubEntityRemovedEvent(): void
    {
        // "contact_removed" removes a contact FROM an account — the account itself
        // (the resource this event is about) still exists, so a link is still useful.
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('accounts');
        $event->getResourceId()->willReturn('5');
        $event->getResourceWebspaceKey()->willReturn(null);
        $event->getEventType()->willReturn('contact_removed');
        $event->getResourceTitle()->willReturn('Acme Inc.');
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);
        $event->getUser()->willReturn(null);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => 'Acme Inc.', '{resourceLocale}' => ''];

        $this->translator->trans('sulu_notifier.subject.accounts.contact_removed', $params, 'admin', 'en')
            ->willReturn('Contact removed');
        $this->translator->trans('sulu_activity.description.accounts.contact_removed', $params, 'admin', 'en')
            ->willReturn('Someone removed a contact from "Acme Inc."');

        $view = new View('sulu_contact.account_edit_form.details', '/contacts/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_contact.account_edit_form.details')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/admin/');

        $resources = ['accounts' => ['views' => ['detail' => 'sulu_contact.account_edit_form.details']]];
        $notification = $this->createFactory(true, $resources)->create($event->reveal(), ['chat/slack']);

        self::assertSame(
            'Someone removed a contact from "Acme Inc."' . "\n\n" . 'https://example.org/admin/#/contacts/5/details',
            $notification->getContent(),
        );
    }

    public function testCreateIncludesLinkForRemovedEvent(): void
    {
        // A "removed" event still links to the resource's detail view -- soft-deleted
        // resources (trash) stay reachable, and even a dead link is preferable to
        // silently dropping the deep link on every removal event.
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('pages');
        $event->getResourceId()->willReturn('3');
        $event->getResourceWebspaceKey()->willReturn('sulu');
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

        $view = new View('sulu_page.page_edit_form.detail', '/webspaces/:webspace/pages/:locale/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_page.page_edit_form.detail')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/admin/');

        $resources = ['pages' => ['views' => ['detail' => 'sulu_page.page_edit_form.detail']]];
        $notification = $this->createFactory(true, $resources)->create($event->reveal(), ['chat/slack']);

        self::assertSame(
            'Someone removed the page "My page"' . "\n\n" . 'https://example.org/admin/#/webspaces/sulu/pages/de/3/details',
            $notification->getContent(),
        );
    }

    public function testCreateIncludesLinkForRemovedNoTrashEvent(): void
    {
        $event = $this->prophesize(DomainEvent::class);
        $event->getResourceKey()->willReturn('media');
        $event->getResourceId()->willReturn('5');
        $event->getResourceWebspaceKey()->willReturn(null);
        $event->getEventType()->willReturn('removed_no_trash');
        $event->getResourceTitle()->willReturn('some-file.jpg');
        $event->getResourceLocale()->willReturn(null);
        $event->getEventContext()->willReturn([]);
        $event->getUser()->willReturn(null);

        $this->translator->trans('sulu_activity.someone', [], 'admin', 'en')->willReturn('Someone');

        $params = ['{userFullName}' => 'Someone', '{resourceTitle}' => 'some-file.jpg', '{resourceLocale}' => ''];

        $this->translator->trans('sulu_notifier.subject.media.removed_no_trash', $params, 'admin', 'en')
            ->willReturn('Media removed');
        $this->translator->trans('sulu_activity.description.media.removed_no_trash', $params, 'admin', 'en')
            ->willReturn('Someone permanently removed the media "some-file.jpg"');

        $view = new View('sulu_media.media_edit_form.detail', '/media/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_media.media_edit_form.detail')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->urlGenerator->generate('sulu_admin', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/admin/');

        $resources = ['media' => ['views' => ['detail' => 'sulu_media.media_edit_form.detail']]];
        $notification = $this->createFactory(true, $resources)->create($event->reveal(), ['chat/slack']);

        self::assertSame(
            'Someone permanently removed the media "some-file.jpg"' . "\n\n" . 'https://example.org/admin/#/media/5/details',
            $notification->getContent(),
        );
    }

    public function testCreateOmitsLinkWhenNoResourceViewUrlGeneratorInjected(): void
    {
        // sulu_admin.resource_view_url_generator is only wired when the AdminBundle
        // is registered -- console/worker contexts without it must not crash or
        // attempt to generate a link.
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

        $this->urlGenerator->generate(Argument::cetera())->shouldNotBeCalled();

        $notification = $this->createFactory(false)->create($event->reveal(), ['chat/slack']);

        self::assertSame('Someone modified the page "My page"', $notification->getContent());
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

        // "pages" has no "detail" view configured -> ResourceViewNotFoundException, handled silently.
        $this->logger->debug(Argument::cetera())->shouldNotBeCalled();
        $this->logger->info(Argument::cetera())->shouldNotBeCalled();
        $this->logger->warning(Argument::cetera())->shouldNotBeCalled();

        $notification = $this->createFactory(true, [])->create($event->reveal(), ['chat/slack']);

        self::assertSame('Someone modified the page "My page"', $notification->getContent());
    }

    public function testCreateLogsInfoWhenViewNotFound(): void
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

        // Configured view name doesn't exist in the registry -> ViewNotFoundException.
        $this->viewRegistry->findViewByName('sulu_page.not_existing')->willThrow(
            new ViewNotFoundException('sulu_page.not_existing'),
        );

        $this->logger->info(
            'sulu_notifier could not resolve a deep link: view not found',
            ['resourceKey' => 'pages', 'resourceId' => '3'],
        )->shouldBeCalled();

        $resources = ['pages' => ['views' => ['detail' => 'sulu_page.not_existing']]];
        $notification = $this->createFactory(true, $resources)->create($event->reveal(), ['chat/slack']);

        self::assertSame('Someone modified the page "My page"', $notification->getContent());
    }

    public function testCreateLogsWarningWhenViewParameterMissing(): void
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

        // View path needs :webspace, which this event doesn't provide -> ViewParameterNotFoundException.
        $view = new View('sulu_page.page_edit_form.detail', '/webspaces/:webspace/pages/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_page.page_edit_form.detail')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $this->logger->warning(
            'sulu_notifier could not resolve a deep link: missing view parameter',
            ['resourceKey' => 'pages', 'resourceId' => '3', 'parameter' => 'webspace'],
        )->shouldBeCalled();

        $resources = ['pages' => ['views' => ['detail' => 'sulu_page.page_edit_form.detail']]];
        $notification = $this->createFactory(true, $resources)->create($event->reveal(), ['chat/slack']);

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
