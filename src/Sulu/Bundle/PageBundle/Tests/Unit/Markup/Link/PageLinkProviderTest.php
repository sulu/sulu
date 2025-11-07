<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Markup\Link;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\PageBundle\Markup\Link\PageLinkProvider;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageLinkProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ContentRepositoryInterface>
     */
    protected $contentRepository;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    protected $webspaceManager;

    /**
     * @var ObjectProphecy<Request>
     */
    protected $request;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    protected $requestStack;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    protected $translator;

    /**
     * @var string
     */
    protected $environment = 'prod';

    /**
     * @var string
     */
    protected $locale = 'en';

    /**
     * @var string
     */
    protected $scheme = 'http';

    /**
     * @var string
     */
    protected $domain = 'sulu.io';

    /**
     * @var PageLinkProvider
     */
    protected $pageLinkProvider;

    /**
     * @var ObjectProphecy<AccessControlManagerInterface>
     */
    private $accessControlManager;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    public function setUp(): void
    {
        $this->request = $this->prophesize(Request::class);
        $this->contentRepository = $this->prophesize(ContentRepositoryInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $this->request->getScheme()->willReturn($this->scheme);
        $this->request->getHost()->willReturn($this->domain);

        $this->pageLinkProvider = new PageLinkProvider(
            $this->contentRepository->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            $this->translator->reveal(),
            $this->environment,
            $this->accessControlManager->reveal(),
            $this->tokenStorage->reveal()
        );
    }

    public function testGetConfiguration(): void
    {
        $this->translator->trans('sulu_page.pages', [], 'admin')->willReturn('Pages');
        $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin')->willReturn('Choose page');
        $this->translator->trans('sulu_page.no_page_selected', [], 'admin')->willReturn('No page selected');

        /** @var LinkConfigurationBuilder $configurationBuilder */
        $configurationBuilder = $this->pageLinkProvider->getConfiguration();
        $this->assertEquals(
            new LinkConfiguration(
                'Pages',
                'pages',
                'column_list',
                ['title'],
                'Choose page',
                'No page selected',
                'su-document',
                ['_blank', '_self', '_parent', '_top'],
            ),
            $configurationBuilder->getLinkConfiguration()
        );
    }

    public function testPreload(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn(new Webspace());

        $contents = [
            $this->createContent(1, 'Test 1', '/test-1'),
            $this->createContent(2, 'Test 2', '/test-2', new \DateTime('-1 day')),
            $this->createContent(3, 'Test 3', '/test-3', new \DateTime('-2 day')),
        ];

        $this->contentRepository->findByUuids(
            [1, 2, 3],
            $this->locale,
            Argument::that(
                function(MappingInterface $mapping) {
                    return $mapping->resolveUrl()
                        && !$mapping->shouldHydrateGhost()
                        && $mapping->onlyPublished()
                        && ['title', 'published'] === $mapping->getProperties();
                }
            )
        )->shouldBeCalledTimes(1)->willReturn($contents);

        /** @var LinkItem[] $result */
        $result = $this->pageLinkProvider->preload([1, 2, 3], $this->locale, true);

        $this->assertCount(3, $result);

        $this->assertEquals($contents[0]->getId(), $result[0]->getId());
        $this->assertEquals('/' . $this->locale . $contents[0]->getUrl(), $result[0]->getUrl());
        $this->assertEquals($contents[0]->getPropertyWithDefault('title'), $result[0]->getTitle());
        $this->assertEquals(!empty($contents[0]->getPropertyWithDefault('published')), $result[0]->isPublished());

        $this->assertEquals($contents[1]->getId(), $result[1]->getId());
        $this->assertEquals('/' . $this->locale . $contents[1]->getUrl(), $result[1]->getUrl());
        $this->assertEquals($contents[1]->getPropertyWithDefault('title'), $result[1]->getTitle());
        $this->assertEquals(!empty($contents[1]->getPropertyWithDefault('published')), $result[1]->isPublished());

        $this->assertEquals($contents[2]->getId(), $result[2]->getId());
        $this->assertEquals('/' . $this->locale . $contents[2]->getUrl(), $result[2]->getUrl());
        $this->assertEquals($contents[2]->getPropertyWithDefault('title'), $result[2]->getTitle());
        $this->assertEquals(!empty($contents[2]->getPropertyWithDefault('published')), $result[2]->isPublished());
    }

    public function testPreloadRemoved(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn(new Webspace());

        $contents = [
            $this->createContent(1, 'Test 1', '/test-1'),
            $this->createContent(2, 'Test 2', '/test-2', new \DateTime('-1 day')),
        ];

        $this->contentRepository->findByUuids(
            [1, 2, 3],
            $this->locale,
            Argument::that(
                function(MappingInterface $mapping) {
                    return $mapping->resolveUrl()
                        && !$mapping->shouldHydrateGhost()
                        && $mapping->onlyPublished()
                        && ['title', 'published'] === $mapping->getProperties();
                }
            )
        )->shouldBeCalledTimes(1)->willReturn($contents);

        /** @var LinkItem[] $result */
        $result = $this->pageLinkProvider->preload([1, 2, 3], $this->locale, true);

        $this->assertCount(2, $result);

        $this->assertEquals($contents[0]->getId(), $result[0]->getId());
        $this->assertEquals('/' . $this->locale . $contents[0]->getUrl(), $result[0]->getUrl());
        $this->assertEquals($contents[0]->getPropertyWithDefault('title'), $result[0]->getTitle());
        $this->assertEquals(!empty($contents[0]->getPropertyWithDefault('published')), $result[0]->isPublished());

        $this->assertEquals($contents[1]->getId(), $result[1]->getId());
        $this->assertEquals('/' . $this->locale . $contents[1]->getUrl(), $result[1]->getUrl());
        $this->assertEquals($contents[1]->getPropertyWithDefault('title'), $result[1]->getTitle());
        $this->assertEquals(!empty($contents[1]->getPropertyWithDefault('published')), $result[1]->isPublished());
    }

    public function testPreloadNoRequest(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn(new Webspace());

        $contents = [
            $this->createContent(1, 'Test 1', '/test-1', null, null),
        ];

        $this->contentRepository->findByUuids(
            [1],
            $this->locale,
            Argument::that(
                function(MappingInterface $mapping) {
                    return $mapping->resolveUrl()
                    && !$mapping->shouldHydrateGhost()
                    && $mapping->onlyPublished()
                    && ['title', 'published'] === $mapping->getProperties();
                }
            )
        )->shouldBeCalledTimes(1)->willReturn($contents);

        /** @var LinkItem[] $result */
        $result = $this->pageLinkProvider->preload([1], $this->locale, true);

        $this->assertCount(1, $result);

        $this->assertEquals($contents[0]->getId(), $result[0]->getId());
        $this->assertEquals('/' . $this->locale . $contents[0]->getUrl(), $result[0]->getUrl());
        $this->assertEquals($contents[0]->getPropertyWithDefault('title'), $result[0]->getTitle());
        $this->assertEquals(!empty($contents[0]->getPropertyWithDefault('published')), $result[0]->isPublished());
    }

    public function testPreloadWithSecurityAndWebsiteSecurityEnabled(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $webspace = new Webspace();
        $security = new Security();
        $security->setSystem('website');
        $webspace->setSecurity($security);
        $security->setPermissionCheck(true);
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        $user = new User();
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $contents = [
            $this->createContent(1, 'Test 1', '/test-1', null, 'sulu.io', 'sulu_io', []),
            $this->createContent(2, 'Test 2', '/test-2', null, 'sulu.io', 'sulu_io', [1 => ['view' => false]]),
            $this->createContent(3, 'Test 3', '/test-3', null, 'sulu.io', 'sulu_io', [1 => ['view' => true]]),
        ];

        $this->accessControlManager
             ->getUserPermissionByArray($this->locale, 'sulu.webspaces.sulu_io', [], $user, 'website')
             ->willReturn([]);
        $this->accessControlManager
             ->getUserPermissionByArray(
                 $this->locale,
                 'sulu.webspaces.sulu_io',
                 [1 => ['view' => false]],
                 $user,
                 'website'
             )
             ->willReturn(['view' => false]);
        $this->accessControlManager
             ->getUserPermissionByArray(
                 $this->locale,
                 'sulu.webspaces.sulu_io',
                 [1 => ['view' => true]],
                 $user,
                 'website'
             )
             ->willReturn(['view' => true]);

        $this->contentRepository->findByUuids(
            [1, 2, 3],
            $this->locale,
            Argument::that(
                function(MappingInterface $mapping) {
                    return $mapping->resolveUrl()
                        && !$mapping->shouldHydrateGhost()
                        && $mapping->onlyPublished()
                        && ['title', 'published'] === $mapping->getProperties();
                }
            )
        )->shouldBeCalledTimes(1)->willReturn($contents);

        /** @var LinkItem[] $result */
        $result = $this->pageLinkProvider->preload([1, 2, 3], $this->locale, true);

        $this->assertCount(2, $result);

        $this->assertEquals($contents[0]->getId(), $result[0]->getId());
        $this->assertEquals($contents[2]->getId(), $result[2]->getId());
    }

    public function testPreloadWithSecurity(): void
    {
        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $webspace = new Webspace();
        $security = new Security();
        $security->setSystem('website');
        $webspace->setSecurity($security);
        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace);

        $user = new User();
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user);
        $this->tokenStorage->getToken()->willReturn($token->reveal());

        $contents = [
            $this->createContent(1, 'Test 1', '/test-1', null, 'sulu.io', 'sulu_io', []),
            $this->createContent(2, 'Test 2', '/test-2', null, 'sulu.io', 'sulu_io', [1 => ['view' => false]]),
            $this->createContent(3, 'Test 3', '/test-3', null, 'sulu.io', 'sulu_io', [1 => ['view' => true]]),
        ];

        $this->accessControlManager
            ->getUserPermissionByArray($this->locale, 'sulu.webspaces.sulu_io', [], $user, 'website')
            ->willReturn([]);
        $this->accessControlManager
            ->getUserPermissionByArray(
                $this->locale,
                'sulu.webspaces.sulu_io',
                [1 => ['view' => false]],
                $user,
                'website'
            )
            ->willReturn(['view' => false]);
        $this->accessControlManager
            ->getUserPermissionByArray(
                $this->locale,
                'sulu.webspaces.sulu_io',
                [1 => ['view' => true]],
                $user,
                'website'
            )
            ->willReturn(['view' => true]);

        $this->contentRepository->findByUuids(
            [1, 2, 3],
            $this->locale,
            Argument::that(
                function(MappingInterface $mapping) {
                    return $mapping->resolveUrl()
                        && !$mapping->shouldHydrateGhost()
                        && $mapping->onlyPublished()
                        && ['title', 'published'] === $mapping->getProperties();
                }
            )
        )->shouldBeCalledTimes(1)->willReturn($contents);

        /** @var LinkItem[] $result */
        $result = $this->pageLinkProvider->preload([1, 2, 3], $this->locale, true);

        $this->assertCount(3, $result);

        $this->assertEquals($contents[0]->getId(), $result[0]->getId());
        $this->assertEquals($contents[2]->getId(), $result[2]->getId());
    }

    /**
     * Create content prophecy.
     *
     * @param string $id
     * @param string $title
     * @param string $url
     * @param \DateTime|null $published
     * @param string|null $domain
     *
     * @return Content
     */
    private function createContent(
        $id,
        $title,
        $url,
        $published = null,
        $domain = 'sulu.io',
        $webspaceKey = 'sulu_io',
        $permissions = []
    ) {
        $content = $this->prophesize(Content::class);
        $content->getId()->willReturn($id);
        $content->getUrl()->willReturn($url);
        $content->getLocale()->willReturn($this->locale);
        $content->getPermissions()->willReturn($permissions);
        $content->getWebspaceKey()->willReturn($webspaceKey);
        $content->getPropertyWithDefault('title', null)->willReturn($title);
        $content->getPropertyWithDefault('published', null)->willReturn($published);

        $this->webspaceManager->findUrlByResourceLocator(
            $url,
            $this->environment,
            $this->locale,
            $webspaceKey,
            $domain,
            $this->scheme
        )->will(
            function($arguments) {
                return \sprintf('/%s%s', $arguments[2], $arguments[0]);
            }
        );

        return $content->reveal();
    }
}
