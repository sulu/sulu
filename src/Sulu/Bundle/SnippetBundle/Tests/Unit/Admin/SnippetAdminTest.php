<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class SnippetAdminTest extends TestCase
{
    use ProphecyTrait;
    use ReadObjectAttributeTrait;

    /**
     * @var ViewBuilderFactory
     */
    private $viewBuilderFactory;

    /**
     * @var ObjectProphecy<SecurityChecker>
     */
    private $securityChecker;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    public function setUp(): void
    {
        $this->viewBuilderFactory = new ViewBuilderFactory();
        $this->securityChecker = $this->prophesize(SecurityChecker::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
    }

    public function provideConfigureViews()
    {
        return [
            [['en' => 'en', 'de' => 'de', 'fr' => 'fr']],
            [['en' => 'en']],
        ];
    }

    /**
     * @dataProvider provideConfigureViews
     */
    public function testConfigureViews($locales): void
    {
        $snippetAdmin = new SnippetAdmin(
            $this->viewBuilderFactory,
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            false,
            'Test'
        );

        $this->securityChecker->hasPermission('sulu.global.snippets', 'add')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'edit')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'delete')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'view')->willReturn(true);

        $this->webspaceManager->getAllLocales()->willReturn(\array_values($locales));

        $viewCollection = new ViewCollection();
        $snippetAdmin->configureViews($viewCollection);

        $listView = $viewCollection->get('sulu_snippet.list')->getView();
        $addFormView = $viewCollection->get('sulu_snippet.add_form')->getView();
        $addDetailView = $viewCollection->get('sulu_snippet.add_form.details')->getView();
        $editFormView = $viewCollection->get('sulu_snippet.edit_form')->getView();
        $editDetailView = $viewCollection->get('sulu_snippet.edit_form.details')->getView();

        $this->assertEquals('sulu_snippet.list', $listView->getName());
        $this->assertEquals([
            'title' => 'sulu_snippet.snippets',
            'toolbarActions' => [
                new ToolbarAction('sulu_admin.add'),
                new ToolbarAction('sulu_admin.delete'),
                new ToolbarAction('sulu_admin.export'),
            ],
            'resourceKey' => 'snippets',
            'listKey' => 'snippets',
            'adapters' => ['table'],
            'addView' => 'sulu_snippet.add_form',
            'editView' => 'sulu_snippet.edit_form',
            'locales' => \array_keys($locales),
        ], $this->readObjectAttribute($listView, 'options'));
        $this->assertEquals(['locale' => \array_keys($locales)[0]], $this->readObjectAttribute($listView, 'attributeDefaults'));
        $this->assertEquals('sulu_snippet.add_form', $addFormView->getName());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'backView' => 'sulu_snippet.list',
            'locales' => \array_keys($locales),
        ], $this->readObjectAttribute($addFormView, 'options'));
        $this->assertEquals('sulu_snippet.add_form', $addDetailView->getParent());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'tabTitle' => 'sulu_admin.details',
            'formKey' => 'snippet',
            'editView' => 'sulu_snippet.edit_form',
            'toolbarActions' => [
                new Toolbaraction('sulu_admin.save'),
                new Toolbaraction('sulu_admin.type', ['sort_by' => 'title']),
                new Toolbaraction('sulu_admin.delete'),
            ],
        ], $this->readObjectAttribute($addDetailView, 'options'));
        $this->assertEquals('sulu_snippet.edit_form', $editFormView->getName());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'backView' => 'sulu_snippet.list',
            'locales' => \array_keys($locales),
            'titleProperty' => 'title',
        ], $this->readObjectAttribute($editFormView, 'options'));
        $this->assertEquals('sulu_snippet.edit_form.details', $editDetailView->getName());
        $this->assertEquals('sulu_snippet.edit_form', $editDetailView->getParent());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'tabTitle' => 'sulu_admin.details',
            'formKey' => 'snippet',
            'toolbarActions' => [
                new Toolbaraction('sulu_admin.save'),
                new Toolbaraction('sulu_admin.type', ['sort_by' => 'title']),
                new Toolbaraction('sulu_admin.delete'),
            ],
        ], $this->readObjectAttribute($editDetailView, 'options'));
    }
}
