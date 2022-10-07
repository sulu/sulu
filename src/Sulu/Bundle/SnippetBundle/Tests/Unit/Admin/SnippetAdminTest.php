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
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactory;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Sulu\Component\Security\Authorization\SecurityChecker;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class SnippetAdminTest extends TestCase
{
    use ReadObjectAttributeTrait;

    /**
     * @var ViewBuilderFactory
     */
    private $viewBuilderFactory;

    /**
     * @var SecurityChecker
     */
    private $securityChecker;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var MetadataProviderInterface
     */
    private $formMetadataProvider;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function setUp(): void
    {
        $this->viewBuilderFactory = new ViewBuilderFactory();
        $this->securityChecker = $this->prophesize(SecurityChecker::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->formMetadataProvider = $this->prophesize(FormMetadataProvider::class);
        $this->tokenStorage = $this->prophesize(TokenStorage::class);
        $this->token = $this->prophesize(TokenInterface::class);
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
    public function testConfigureSingleListViews($locales)
    {
        $snippetAdmin = new SnippetAdmin(
            $this->viewBuilderFactory,
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            false,
            SnippetAdmin::VIEW_SINGLE_LIST,
            $this->formMetadataProvider->reveal(),
            $this->tokenStorage->reveal()
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
            'locales' => \array_keys($locales)
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

    /**
     * @dataProvider provideConfigureViews
     */
    public function testConfigureTypesListViews($locales)
    {
        $snippetAdmin = new SnippetAdmin(
            $this->viewBuilderFactory,
            $this->securityChecker->reveal(),
            $this->webspaceManager->reveal(),
            false,
            SnippetAdmin::VIEW_TYPES_LIST,
            $this->formMetadataProvider->reveal(),
            $this->tokenStorage->reveal()
        );

        $this->securityChecker->hasPermission('sulu.global.snippets', 'add')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'edit')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'delete')->willReturn(true);
        $this->securityChecker->hasPermission('sulu.global.snippets', 'view')->willReturn(true);

        $this->webspaceManager->getAllLocales()->willReturn(\array_values($locales));

        $user = new User();
        $user->setLocale('en');
        $this->token->getUser()->willReturn($user);
        $this->tokenStorage->getToken()->willReturn($this->token);

        $type1 = new FormMetadata();
        $type1->setKey('type1');
        $type1->setName('type1');
        $type1->setTitle('Snippet type 1');
        $formMetadata = new TypedFormMetadata();
        $formMetadata->addForm('type1', $type1);
        $this->formMetadataProvider->getMetadata('snippet', 'en', [])->willReturn($formMetadata);

        $viewCollection = new ViewCollection();
        $snippetAdmin->configureViews($viewCollection);

        $listView = $viewCollection->get('sulu_snippet.list_type1')->getView();
        $addFormView = $viewCollection->get('sulu_snippet.add_form_type1')->getView();
        $addDetailView = $viewCollection->get('sulu_snippet.add_form.details_type1')->getView();
        $editFormView = $viewCollection->get('sulu_snippet.edit_form_type1')->getView();
        $editDetailView = $viewCollection->get('sulu_snippet.edit_form.details_type1')->getView();

        $this->assertEquals('sulu_snippet.list_type1', $listView->getName());
        $this->assertEquals([
            'title' => 'Snippet type 1',
            'toolbarActions' => [
                new ToolbarAction('sulu_admin.add'),
                new ToolbarAction('sulu_admin.delete'),
                new ToolbarAction('sulu_admin.export'),
            ],
            'resourceKey' => 'snippets',
            'listKey' => 'snippets',
            'adapters' => ['table'],
            'addView' => 'sulu_snippet.add_form_type1',
            'editView' => 'sulu_snippet.edit_form_type1',
            'locales' => \array_keys($locales),
            'requestParameters' => ['types' => 'type1']
        ], $this->readObjectAttribute($listView, 'options'));
        $this->assertEquals(['locale' => \array_keys($locales)[0]], $this->readObjectAttribute($listView, 'attributeDefaults'));
        $this->assertEquals('sulu_snippet.add_form_type1', $addFormView->getName());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'backView' => 'sulu_snippet.list_type1',
            'locales' => \array_keys($locales),
        ], $this->readObjectAttribute($addFormView, 'options'));
        $this->assertEquals('sulu_snippet.add_form_type1', $addDetailView->getParent());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'tabTitle' => 'sulu_admin.details',
            'formKey' => 'snippet',
            'editView' => 'sulu_snippet.edit_form_type1',
            'toolbarActions' => [
                new Toolbaraction('sulu_admin.save'),
                new Toolbaraction('sulu_admin.type', ['sort_by' => 'title']),
                new Toolbaraction('sulu_admin.delete'),
            ],
            'metadataRequestParameters' => ['defaultType' => 'type1'],
        ], $this->readObjectAttribute($addDetailView, 'options'));
        $this->assertEquals('sulu_snippet.edit_form_type1', $editFormView->getName());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'backView' => 'sulu_snippet.list_type1',
            'locales' => \array_keys($locales),
            'titleProperty' => 'title',
        ], $this->readObjectAttribute($editFormView, 'options'));
        $this->assertEquals('sulu_snippet.edit_form.details_type1', $editDetailView->getName());
        $this->assertEquals('sulu_snippet.edit_form_type1', $editDetailView->getParent());
        $this->assertEquals([
            'resourceKey' => 'snippets',
            'tabTitle' => 'sulu_admin.details',
            'formKey' => 'snippet',
            'toolbarActions' => [
                new Toolbaraction('sulu_admin.save'),
                new Toolbaraction('sulu_admin.type', ['sort_by' => 'title']),
                new Toolbaraction('sulu_admin.delete'),
            ],
            'metadataRequestParameters' => ['defaultType' => 'type1'],
        ], $this->readObjectAttribute($editDetailView, 'options'));
    }
}
