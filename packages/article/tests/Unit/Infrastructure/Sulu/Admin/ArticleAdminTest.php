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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\ActivityBundle\Infrastructure\Sulu\Admin\View\ActivityViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;

class ArticleAdminTest extends TestCase
{
    use ProphecyTrait;

    private ViewBuilderFactory $viewBuilderFactory;

    /**
     * @var ObjectProphecy<ContentViewBuilderFactoryInterface>
     */
    private ObjectProphecy $contentViewBuilderFactory;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private ObjectProphecy $securityChecker;

    /**
     * @var ObjectProphecy<LocalizationManagerInterface>
     */
    private ObjectProphecy $localizationManager;

    /**
     * @var ObjectProphecy<ActivityViewBuilderFactoryInterface>
     */
    private ObjectProphecy $activityViewBuilderFactory;

    /**
     * @var ObjectProphecy<GroupProviderInterface>
     */
    private ObjectProphecy $groupProvider;

    private ArticleAdmin $articleAdmin;

    protected function setUp(): void
    {
        $this->viewBuilderFactory = new ViewBuilderFactory();
        $this->contentViewBuilderFactory = $this->prophesize(ContentViewBuilderFactoryInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);
        $this->activityViewBuilderFactory = $this->prophesize(ActivityViewBuilderFactoryInterface::class);
        $this->groupProvider = $this->prophesize(GroupProviderInterface::class);

        $this->contentViewBuilderFactory->getDefaultToolbarActions(Argument::cetera())->willReturn([]);
        $this->contentViewBuilderFactory->createViews(Argument::cetera())->willReturn([]);
        $this->activityViewBuilderFactory->hasActivityListPermission()->willReturn(false);

        $this->articleAdmin = new ArticleAdmin(
            $this->viewBuilderFactory,
            $this->contentViewBuilderFactory->reveal(),
            $this->securityChecker->reveal(),
            $this->localizationManager->reveal(),
            $this->activityViewBuilderFactory->reveal(),
            $this->groupProvider->reveal(),
        );
    }

    public function testConfigureNavigationItemsWithNoGroups(): void
    {
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn([]);

        $navigationItemCollection = new NavigationItemCollection();
        $this->articleAdmin->configureNavigationItems($navigationItemCollection);

        $this->assertFalse($navigationItemCollection->has('sulu_article.articles'));
    }

    public function testConfigureNavigationItemsWithSingleGroupAndPermission(): void
    {
        // With a single (implicit "default") group, getSecurityContexts() registers only
        // ArticleAdmin::SECURITY_CONTEXT — not the per-group context. The navigation item
        // must therefore be visible when the user holds the base context permission, even
        // though the group identifier alone would point to an unregistered context.
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['default' => new FormGroup('default', 'Default')]);

        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, PermissionTypes::EDIT)
            ->willReturn(true);
        $this->securityChecker->hasPermission(Argument::not(ArticleAdmin::SECURITY_CONTEXT), Argument::any())
            ->willReturn(false);

        $navigationItemCollection = new NavigationItemCollection();
        $this->articleAdmin->configureNavigationItems($navigationItemCollection);

        $this->assertTrue($navigationItemCollection->has('sulu_article.articles'));
        $navigationItem = $navigationItemCollection->get('sulu_article.articles');
        $this->assertSame(ArticleAdmin::LIST_VIEW, $navigationItem->getView());
        $this->assertSame('su-newspaper', $navigationItem->getIcon());
    }

    public function testConfigureNavigationItemsWithSingleGroupAndNoPermission(): void
    {
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['default' => new FormGroup('default', 'Default')]);

        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(false);

        $navigationItemCollection = new NavigationItemCollection();
        $this->articleAdmin->configureNavigationItems($navigationItemCollection);

        $this->assertFalse($navigationItemCollection->has('sulu_article.articles'));
    }

    public function testConfigureNavigationItemsWithMultipleGroupsAndPermissionOnOne(): void
    {
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn([
            'blog-group' => new FormGroup('blog-group', 'Blog'),
            'news-group' => new FormGroup('news-group', 'News'),
        ]);

        $this->securityChecker->hasPermission(
            ArticleAdmin::getArticleSecurityContext('blog-group'),
            PermissionTypes::EDIT
        )->willReturn(false);
        $this->securityChecker->hasPermission(
            ArticleAdmin::getArticleSecurityContext('news-group'),
            PermissionTypes::EDIT
        )->willReturn(true);

        $navigationItemCollection = new NavigationItemCollection();
        $this->articleAdmin->configureNavigationItems($navigationItemCollection);

        $this->assertTrue($navigationItemCollection->has('sulu_article.articles'));
    }

    public function testConfigureNavigationItemsWithMultipleGroupsAndNoPermission(): void
    {
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn([
            'blog-group' => new FormGroup('blog-group', 'Blog'),
            'news-group' => new FormGroup('news-group', 'News'),
        ]);

        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(false);

        $navigationItemCollection = new NavigationItemCollection();
        $this->articleAdmin->configureNavigationItems($navigationItemCollection);

        $this->assertFalse($navigationItemCollection->has('sulu_article.articles'));
    }

    public function testConfigureViewsAlwaysAddsRootTabView(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en']);
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn([]);
        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(false);

        $viewCollection = new ViewCollection();
        $this->articleAdmin->configureViews($viewCollection);

        $this->assertTrue($viewCollection->has(ArticleAdmin::LIST_VIEW));
    }

    public function testConfigureViewsWithSingleGroupAndPermission(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en', 'de']);
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['default' => (new FormGroup('default', 'Default'))->withTemplate('article')]);

        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, PermissionTypes::EDIT)
            ->willReturn(true);
        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, Argument::not(PermissionTypes::EDIT))
            ->willReturn(false);
        $this->securityChecker->hasPermission(Argument::not(ArticleAdmin::SECURITY_CONTEXT), Argument::any())
            ->willReturn(false);

        $viewCollection = new ViewCollection();
        $this->articleAdmin->configureViews($viewCollection);

        $this->assertTrue($viewCollection->has(ArticleAdmin::LIST_VIEW));
        $this->assertTrue($viewCollection->has(ArticleAdmin::LIST_VIEW . '_default'));
        $this->assertTrue($viewCollection->has(ArticleAdmin::ADD_TABS_VIEW . '_default'));
        $this->assertTrue($viewCollection->has(ArticleAdmin::EDIT_TABS_VIEW . '_default'));
    }

    public function testConfigureViewsWithSingleGroupAndNoPermission(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en']);
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['default' => new FormGroup('default', 'Default')]);

        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(false);

        $viewCollection = new ViewCollection();
        $this->articleAdmin->configureViews($viewCollection);

        $this->assertTrue($viewCollection->has(ArticleAdmin::LIST_VIEW));
        $this->assertFalse($viewCollection->has(ArticleAdmin::LIST_VIEW . '_default'));
        $this->assertFalse($viewCollection->has(ArticleAdmin::ADD_TABS_VIEW . '_default'));
        $this->assertFalse($viewCollection->has(ArticleAdmin::EDIT_TABS_VIEW . '_default'));
    }

    public function testConfigureViewsWithSingleGroupGrantsToolbarActionsViaBaseContext(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en']);
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['default' => (new FormGroup('default', 'Default'))->withTemplate('article')]);

        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, Argument::any())->willReturn(true);
        $this->securityChecker->hasPermission(Argument::not(ArticleAdmin::SECURITY_CONTEXT), Argument::any())
            ->willReturn(false);

        $viewCollection = new ViewCollection();
        $this->articleAdmin->configureViews($viewCollection);

        $this->assertToolbarActions(
            ['sulu_admin.add', 'sulu_admin.delete', 'sulu_admin.export'],
            $viewCollection,
            ArticleAdmin::LIST_VIEW . '_default',
        );
    }

    public function testConfigureViewsWithMultipleGroups(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en']);
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn([
            'blog-group' => (new FormGroup('blog-group', 'Blog'))->withTemplate('blog'),
            'news-group' => (new FormGroup('news-group', 'News'))->withTemplate('news'),
        ]);

        $blogContext = ArticleAdmin::getArticleSecurityContext('blog-group');
        $newsContext = ArticleAdmin::getArticleSecurityContext('news-group');

        $this->securityChecker->hasPermission($blogContext, PermissionTypes::EDIT)->willReturn(true);
        $this->securityChecker->hasPermission($newsContext, PermissionTypes::EDIT)->willReturn(false);
        $this->securityChecker->hasPermission(Argument::cetera())->willReturn(false);

        $viewCollection = new ViewCollection();
        $this->articleAdmin->configureViews($viewCollection);

        $this->assertTrue($viewCollection->has(ArticleAdmin::LIST_VIEW));
        $this->assertTrue($viewCollection->has(ArticleAdmin::LIST_VIEW . '_blog-group'));
        $this->assertTrue($viewCollection->has(ArticleAdmin::ADD_TABS_VIEW . '_blog-group'));
        $this->assertTrue($viewCollection->has(ArticleAdmin::EDIT_TABS_VIEW . '_blog-group'));
        $this->assertFalse($viewCollection->has(ArticleAdmin::LIST_VIEW . '_news-group'));
        $this->assertFalse($viewCollection->has(ArticleAdmin::ADD_TABS_VIEW . '_news-group'));
        $this->assertFalse($viewCollection->has(ArticleAdmin::EDIT_TABS_VIEW . '_news-group'));
    }

    public function testConfigureViewsWithMultipleGroupsScopesToolbarActionsPerGroup(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en']);
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn([
            'blog-group' => (new FormGroup('blog-group', 'Blog'))->withTemplate('blog'),
            'news-group' => (new FormGroup('news-group', 'News'))->withTemplate('news'),
        ]);

        $blogContext = ArticleAdmin::getArticleSecurityContext('blog-group');
        $newsContext = ArticleAdmin::getArticleSecurityContext('news-group');

        $this->securityChecker->hasPermission($blogContext, Argument::any())->willReturn(true);
        $this->securityChecker->hasPermission($newsContext, PermissionTypes::EDIT)->willReturn(true);
        $this->securityChecker->hasPermission($newsContext, Argument::not(PermissionTypes::EDIT))->willReturn(false);
        $this->securityChecker->hasPermission(ArticleAdmin::SECURITY_CONTEXT, Argument::any())->willReturn(false);

        $viewCollection = new ViewCollection();
        $this->articleAdmin->configureViews($viewCollection);

        $this->assertToolbarActions(
            ['sulu_admin.add', 'sulu_admin.delete', 'sulu_admin.export'],
            $viewCollection,
            ArticleAdmin::LIST_VIEW . '_blog-group',
        );
        $this->assertToolbarActions([], $viewCollection, ArticleAdmin::LIST_VIEW . '_news-group');
    }

    public function testGetSecurityContextsWithSingleGroupRegistersOnlyBaseContext(): void
    {
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)
            ->willReturn(['default' => new FormGroup('default', 'Default')]);

        $securityContexts = $this->articleAdmin->getSecurityContexts();

        $this->assertArrayHasKey(ArticleAdmin::SECURITY_CONTEXT, $securityContexts['Sulu']['Article']);
        $this->assertArrayNotHasKey(
            ArticleAdmin::getArticleSecurityContext('default'),
            $securityContexts['Sulu']['Article']
        );
        $this->assertCount(1, $securityContexts['Sulu']['Article']);
    }

    public function testGetSecurityContextsWithMultipleGroupsRegistersPerGroupContexts(): void
    {
        $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE)->willReturn([
            'blog-group' => new FormGroup('blog-group', 'Blog'),
            'news-group' => new FormGroup('news-group', 'News'),
        ]);

        $securityContexts = $this->articleAdmin->getSecurityContexts();

        $this->assertArrayHasKey(ArticleAdmin::SECURITY_CONTEXT, $securityContexts['Sulu']['Article']);
        $this->assertArrayHasKey(
            ArticleAdmin::getArticleSecurityContext('blog-group'),
            $securityContexts['Sulu']['Article']
        );
        $this->assertArrayHasKey(
            ArticleAdmin::getArticleSecurityContext('news-group'),
            $securityContexts['Sulu']['Article']
        );
    }

    public function testGetArticleSecurityContext(): void
    {
        $this->assertSame(
            ArticleAdmin::SECURITY_CONTEXT . '_blog-group',
            ArticleAdmin::getArticleSecurityContext('blog-group')
        );
    }

    /**
     * @param string[] $expectedActions
     */
    private function assertToolbarActions(array $expectedActions, ViewCollection $viewCollection, string $viewName): void
    {
        $this->assertTrue($viewCollection->has($viewName), \sprintf('Expected view "%s" to be configured', $viewName));

        /** @var ToolbarAction[] $toolbarActions */
        $toolbarActions = $viewCollection->get($viewName)->getView()->getOption('toolbarActions') ?? [];
        $actionNames = [];
        foreach ($toolbarActions as $toolbarAction) {
            $actionNames[] = $toolbarAction->getType();
        }

        $this->assertSame($expectedActions, $actionNames);
    }
}
