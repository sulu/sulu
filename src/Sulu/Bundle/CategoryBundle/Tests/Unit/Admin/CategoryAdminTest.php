<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\FormOverlayListViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ListViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceTabViewBuilderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class CategoryAdminTest extends TestCase
{
    /**
     * @var ObjectProphecy<ViewBuilderFactoryInterface>
     */
    private $viewBuilderFactory;

    /**
     * @var ObjectProphecy<SecurityCheckerInterface>
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<LocalizationManagerInterface>
     */
    private $localizationManager;

    /**
     * @var CategoryAdmin
     */
    private $categoryAdmin;

    /**
     * @var ObjectProphecy<ListViewBuilderInterface>
     */
    private $listViewBuilder;

    /**
     * @var ObjectProphecy<ResourceTabViewBuilderInterface>
     */
    private $resourceTabViewBuilder;

    /**
     * @var ObjectProphecy<FormViewBuilderInterface>
     */
    private $formViewBuilder;

    /**
     * @var ObjectProphecy<FormOverlayListViewBuilderInterface>
     */
    private $formOverlayListBuilder;

    /**
     * @var string[]
     */
    private $locales;

    public function setUp(): void
    {
        $this->locales = ['en'];
        $this->viewBuilderFactory = $this->prophesize(ViewBuilderFactoryInterface::class);
        $this->securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);
        $this->categoryAdmin = new CategoryAdmin(
            $this->viewBuilderFactory->reveal(),
            $this->securityChecker->reveal(),
            $this->localizationManager->reveal()
        );

        $this->listViewBuilder = $this->prophesize(ListViewBuilderInterface::class)->willBeConstructedWith();
        $this->listViewBuilder->getName()->willReturn('listViewName');

        $this->resourceTabViewBuilder = $this->prophesize(ResourceTabViewBuilderInterface::class);
        $this->resourceTabViewBuilder->getName()->willReturn('resourceTabViewName');

        $this->formViewBuilder = $this->prophesize(FormViewBuilderInterface::class);
        $this->formViewBuilder->getName()->willReturn('formViewName');

        $this->formOverlayListBuilder = $this->prophesize(FormOverlayListViewBuilderInterface::class);
        $this->formOverlayListBuilder->getName()->willReturn('formOverlayListName');

        $this->localizationManager->getLocales()->willReturn($this->locales);

        $this->viewBuilderFactory->createListViewBuilder(CategoryAdmin::LIST_VIEW, '/categories/:locale')
             ->willReturn($this->listViewBuilder->reveal());
        $this->listViewBuilder->setResourceKey(CategoryInterface::RESOURCE_KEY)->willReturn($this->listViewBuilder->reveal());
        $this->listViewBuilder->setListKey('categories')->willReturn($this->listViewBuilder->reveal());
        $this->listViewBuilder->setTitle('sulu_category.categories')->willReturn($this->listViewBuilder->reveal());
        $this->listViewBuilder->addListAdapters(['tree_table'])->willReturn($this->listViewBuilder->reveal());
        $this->listViewBuilder->addLocales($this->locales)->willReturn($this->listViewBuilder->reveal());
        $this->listViewBuilder->enableSearching()->willReturn($this->listViewBuilder->reveal());

        $this->viewBuilderFactory->createResourceTabViewBuilder(CategoryAdmin::ADD_FORM_VIEW, '/categories/:locale/add')
             ->willReturn($this->resourceTabViewBuilder->reveal());
        $this->resourceTabViewBuilder->setResourceKey(CategoryInterface::RESOURCE_KEY)->willReturn($this->resourceTabViewBuilder->reveal());
        $this->resourceTabViewBuilder->addLocales($this->locales)->willReturn($this->resourceTabViewBuilder->reveal());
        $this->resourceTabViewBuilder->setBackView(CategoryAdmin::LIST_VIEW)->willReturn($this->resourceTabViewBuilder->reveal());

        $this->viewBuilderFactory->createFormViewBuilder('sulu_category.add_form.details', '/details')
             ->willReturn($this->formViewBuilder->reveal());
        $this->formViewBuilder->setResourceKey(CategoryInterface::RESOURCE_KEY)->willReturn($this->formViewBuilder->reveal());
        $this->formViewBuilder->setFormKey('category_details')->willReturn($this->formViewBuilder->reveal());
        $this->formViewBuilder->setTabTitle('sulu_admin.details')->willReturn($this->formViewBuilder->reveal());
        $this->formViewBuilder->addRouterAttributesToFormRequest(['parentId'])->willReturn($this->formViewBuilder->reveal());
        $this->formViewBuilder->setEditView(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->formViewBuilder->reveal());
        $this->formViewBuilder->setParent(CategoryAdmin::ADD_FORM_VIEW)->willReturn($this->formViewBuilder->reveal());

        $this->viewBuilderFactory->createResourceTabViewBuilder(CategoryAdmin::EDIT_FORM_VIEW, '/categories/:locale/:id')
             ->willReturn($this->resourceTabViewBuilder->reveal());
        $this->resourceTabViewBuilder->addRouterAttributesToBackView(['id' => 'active'])->willReturn($this->resourceTabViewBuilder->reveal());
        $this->resourceTabViewBuilder->setTitleProperty('name')->willReturn($this->resourceTabViewBuilder->reveal());

        $this->viewBuilderFactory->createFormViewBuilder('sulu_category.edit_form.details', '/details')
             ->willReturn($this->formViewBuilder->reveal());
        $this->formViewBuilder->setParent(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->formViewBuilder->reveal());

        $this->viewBuilderFactory->createFormOverlayListViewBuilder('sulu_category.edit_form.keywords', '/keywords')
             ->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->setResourceKey('category_keywords')->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->setListKey('category_keywords')->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->addListAdapters(['table'])->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->addRouterAttributesToListRequest(['id' => 'categoryId'])->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->setFormKey('category_keywords')->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->addRouterAttributesToFormRequest(['id' => 'categoryId'])->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->setTabTitle('sulu_category.keywords')->willReturn($this->formOverlayListBuilder->reveal());
        $this->formOverlayListBuilder->setParent(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->formOverlayListBuilder->reveal());
    }

    public function testLocalesAreNotSet(): void
    {
        $viewCollection = new ViewCollection();
        $this->localizationManager->getLocales()->willReturn([]);
        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertEmpty($viewCollection->all());
    }

    public function testUserHasNoRoles(): void
    {
        $viewCollection = new ViewCollection();
        $this->localizationManager->getLocales()->willReturn(['en']);
        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertEmpty($viewCollection->all());
    }

    public function testUserHasNoViewRole(): void
    {
        $viewCollection = new ViewCollection();
        $this->localizationManager->getLocales()->willReturn(['en']);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'add')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'edit')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'delete')->willReturn(true)->shouldBeCalled();
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'view')->willReturn(false);

        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertEmpty($viewCollection->all());
    }

    public function testUserHasViewRole(): void
    {
        $locales = ['en'];
        $listToolbarActions = [new ToolbarAction('sulu_admin.export')];
        $formToolbarActions = [];
        $keywordsToolbarActions = [];
        $viewCollection = new ViewCollection();

        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'add')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'edit')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'delete')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'view')->willReturn(true);

        $this->listViewBuilder->setAddView(CategoryAdmin::ADD_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldNotBeCalled();
        $this->listViewBuilder->setEditView(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldNotBeCalled();
        $this->listViewBuilder->addToolbarActions($listToolbarActions)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->formViewBuilder->addToolbarActions($formToolbarActions)->willReturn($this->formViewBuilder->reveal())->shouldBeCalled();
        $this->formOverlayListBuilder->addToolbarActions($keywordsToolbarActions)->willReturn($this->formOverlayListBuilder->reveal())->shouldBeCalled();

        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertNotEmpty($viewCollection->all());
    }

    public function testUserHasViewAndEditRole(): void
    {
        $locales = ['en'];
        $listToolbarActions = [new ToolbarAction('sulu_admin.move'), new ToolbarAction('sulu_admin.export')];
        $formToolbarActions = [new ToolbarAction('sulu_admin.save')];
        $keywordsToolbarActions = [];
        $viewCollection = new ViewCollection();

        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'add')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'edit')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'delete')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'view')->willReturn(true);

        $this->listViewBuilder->setAddView(CategoryAdmin::ADD_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldNotBeCalled();
        $this->listViewBuilder->setEditView(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->listViewBuilder->addToolbarActions($listToolbarActions)->willReturn($this->listViewBuilder->reveal());
        $this->formViewBuilder->addToolbarActions($formToolbarActions)->willReturn($this->formViewBuilder->reveal())->shouldBeCalled();
        $this->formOverlayListBuilder->addToolbarActions($keywordsToolbarActions)->willReturn($this->formOverlayListBuilder->reveal())->shouldBeCalled();

        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertNotEmpty($viewCollection->all());
    }

    public function testUserHasViewAndAddRole(): void
    {
        $locales = ['en'];
        $listToolbarActions = [new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.export')];
        $formToolbarActions = [new ToolbarAction('sulu_admin.save')];
        $keywordsToolbarActions = [new ToolbarAction('sulu_admin.add')];
        $viewCollection = new ViewCollection();

        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'add')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'edit')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'delete')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'view')->willReturn(true);

        $this->listViewBuilder->setAddView(CategoryAdmin::ADD_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->listViewBuilder->setEditView(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldNotBeCalled();
        $this->listViewBuilder->addToolbarActions($listToolbarActions)->willReturn($this->listViewBuilder->reveal());
        $this->formViewBuilder->addToolbarActions($formToolbarActions)->willReturn($this->formViewBuilder->reveal())->shouldBeCalled();
        $this->formOverlayListBuilder->addToolbarActions($keywordsToolbarActions)->willReturn($this->formOverlayListBuilder->reveal())->shouldBeCalled();

        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertNotEmpty($viewCollection->all());
    }

    public function testUserHasViewAndDeleteRole(): void
    {
        $locales = ['en'];
        $listToolbarActions = [new ToolbarAction('sulu_admin.delete'), new ToolbarAction('sulu_admin.export')];
        $formToolbarActions = [new ToolbarAction('sulu_admin.delete')];
        $keywordsToolbarActions = [new ToolbarAction('sulu_admin.delete')];
        $viewCollection = new ViewCollection();

        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'add')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'edit')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'delete')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'view')->willReturn(true);

        $this->listViewBuilder->setAddView(CategoryAdmin::ADD_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldNotBeCalled();
        $this->listViewBuilder->setEditView(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldNotBeCalled();
        $this->listViewBuilder->addToolbarActions($listToolbarActions)->willReturn($this->listViewBuilder->reveal());
        $this->formViewBuilder->addToolbarActions($formToolbarActions)->willReturn($this->formViewBuilder->reveal())->shouldBeCalled();
        $this->formOverlayListBuilder->addToolbarActions($keywordsToolbarActions)->willReturn($this->formOverlayListBuilder->reveal())->shouldBeCalled();

        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertNotEmpty($viewCollection->all());
    }

    public function testUserHasViewEditAddRole(): void
    {
        $locales = ['en'];
        $listToolbarActions = [new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.move'), new ToolbarAction('sulu_admin.export')];
        $formToolbarActions = [new ToolbarAction('sulu_admin.save')];
        $keywordsToolbarActions = [new ToolbarAction('sulu_admin.add')];
        $viewCollection = new ViewCollection();

        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'add')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'edit')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'delete')->willReturn(false);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'view')->willReturn(true);

        $this->listViewBuilder->setAddView(CategoryAdmin::ADD_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->listViewBuilder->setEditView(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->listViewBuilder->addToolbarActions($listToolbarActions)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->formViewBuilder->addToolbarActions($formToolbarActions)->willReturn($this->formViewBuilder->reveal())->shouldBeCalled();
        $this->formOverlayListBuilder->addToolbarActions($keywordsToolbarActions)->willReturn($this->formOverlayListBuilder->reveal())->shouldBeCalled();

        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertNotEmpty($viewCollection->all());
    }

    public function testUserHasViewEditAddDeleteRole(): void
    {
        $locales = ['en'];
        $listToolbarActions = [new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.move'), new ToolbarAction('sulu_admin.delete'), new ToolbarAction('sulu_admin.export')];
        $formToolbarActions = [new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')];
        $keywordsToolbarActions = [new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.delete')];
        $viewCollection = new ViewCollection();

        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'add')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'edit')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'delete')->willReturn(true);
        $this->securityChecker->hasPermission(CategoryAdmin::SECURITY_CONTEXT, 'view')->willReturn(true);

        $this->listViewBuilder->setAddView(CategoryAdmin::ADD_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->listViewBuilder->setEditView(CategoryAdmin::EDIT_FORM_VIEW)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->listViewBuilder->addToolbarActions($listToolbarActions)->willReturn($this->listViewBuilder->reveal())->shouldBeCalled();
        $this->formViewBuilder->addToolbarActions($formToolbarActions)->willReturn($this->formViewBuilder->reveal())->shouldBeCalled();
        $this->formOverlayListBuilder->addToolbarActions($keywordsToolbarActions)->willReturn($this->formOverlayListBuilder->reveal())->shouldBeCalled();

        $this->categoryAdmin->configureViews($viewCollection);
        $this->assertNotEmpty($viewCollection->all());
    }
}
