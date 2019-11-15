<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\View;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\View\PreviewFormViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;

class PreviewFormViewBuilderTest extends TestCase
{
    public function testBuildPreviewFormViewWithClone()
    {
        $viewBuilder = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles');

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function testBuildPreviewFormViewWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $view = (new PreviewFormViewBuilder('sulu_category.edit_form.details', '/details'))
            ->getView();
    }

    public function provideBuildPreviewFormView()
    {
        return [
            [
                'sulu_category.add_form',
                '/categories/add',
                'categories',
                'categories',
                'Details',
                'name == "Test"',
                100,
                512,
                'sulu_category.edit_form',
                'sulu_category.list',
                false,
                ['test1' => 'value1'],
                true,
                null,
            ],
            [
                'sulu_tag.edit_form',
                '/tags/:id',
                'tags',
                'tags',
                null,
                null,
                null,
                null,
                null,
                null,
                true,
                null,
                false,
                ['webspace'],
            ],
        ];
    }

    /**
     * @dataProvider provideBuildPreviewFormView
     */
    public function testBuildPreviewFormView(
        string $name,
        string $path,
        string $resourceKey,
        string $formKey,
        ?string $tabTitle,
        ?string $tabCondition,
        ?int $tabOrder,
        ?int $tabPriority,
        ?string $editView,
        ?string $backView,
        ?bool $titleVisible,
        ?array $requestParameters,
        ?bool $disablePreviewWebspaceChooser,
        ?array $routerAttributesToFormMetadata
    ) {
        $viewBuilder = (new PreviewFormViewBuilder($name, $path))
            ->setResourceKey($resourceKey)
            ->setFormKey($formKey);

        if ($tabTitle) {
            $viewBuilder->setTabTitle($tabTitle);
        }

        if ($tabCondition) {
            $viewBuilder->setTabCondition($tabCondition);
        }

        if ($tabOrder) {
            $viewBuilder->setTabOrder($tabOrder);
        }

        if ($tabPriority) {
            $viewBuilder->setTabPriority($tabPriority);
        }

        if ($editView) {
            $viewBuilder->setEditView($editView);
        }

        if ($backView) {
            $viewBuilder->setBackView($backView);
        }

        if (null !== $titleVisible) {
            $viewBuilder->setTitleVisible($titleVisible);
        }

        if ($requestParameters) {
            $viewBuilder->setRequestParameters($requestParameters);
        }

        if ($disablePreviewWebspaceChooser) {
            $viewBuilder->disablePreviewWebspaceChooser();
        }

        if ($routerAttributesToFormMetadata) {
            $viewBuilder->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata);
        }

        $view = $viewBuilder->getView();

        $this->assertSame($name, $view->getName());
        $this->assertSame($path, $view->getPath());
        $this->assertSame($resourceKey, $view->getOption('resourceKey'));
        $this->assertSame($formKey, $view->getOption('formKey'));
        $this->assertSame($tabTitle, $view->getOption('tabTitle'));
        $this->assertSame($tabCondition, $view->getOption('tabCondition'));
        $this->assertSame($tabOrder, $view->getOption('tabOrder'));
        $this->assertSame($tabPriority, $view->getOption('tabPriority'));
        $this->assertSame($editView, $view->getOption('editView'));
        $this->assertSame($backView, $view->getOption('backView'));
        $this->assertSame($titleVisible, $view->getOption('titleVisible'));
        $this->assertSame($requestParameters, $view->getOption('requestParameters'));
        $this->assertNull($view->getParent());
        $this->assertSame('sulu_admin.preview_form', $view->getType());
        $this->assertSame($routerAttributesToFormMetadata, $view->getOption('routerAttributesToFormMetadata'));

        if ($disablePreviewWebspaceChooser) {
            $this->assertFalse($view->getOption('previewWebspaceChooser'));
        } else {
            $this->assertNull($view->getOption('previewWebspaceChooser'));
        }
    }

    public function testBuildFormWithToolbarActions()
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addToolbarActions([$saveToolbarAction, $typesToolbarAction])
            ->addToolbarActions([$deleteToolbarAction])
            ->getView();

        $this->assertSame(
            [$saveToolbarAction, $typesToolbarAction, $deleteToolbarAction],
            $view->getOption('toolbarActions')
        );
    }

    public function testBuildFormWithRouterAttributesToFormRequest()
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToFormRequest(['webspace' => 'webspaceId', 'parent' => 'parentId'])
            ->addRouterAttributesToFormRequest(['locale'])
            ->getView();

        $this->assertSame(
            ['webspace' => 'webspaceId', 'parent' => 'parentId', 'locale'],
            $view->getOption('routerAttributesToFormRequest')
        );
    }

    public function testBuildFormWithRouterAttributesToEditView()
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addRouterAttributesToEditView(['webspace', 'parent'])
            ->addRouterAttributesToEditView(['locale'])
            ->getView();

        $this->assertSame(
            ['webspace', 'parent', 'locale'],
            $view->getOption('routerAttributesToEditView')
        );
    }

    public function testBuildFormWithIdQueryParameter()
    {
        $view = (new PreviewFormViewBuilder('sulu_security.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setIdQueryParameter('contactId')
            ->getView();

        $this->assertSame(
            'contactId',
            $view->getOption('idQueryParameter')
        );
    }

    public function testBuildFormWithPreviewCondition()
    {
        $view = (new PreviewFormViewBuilder('sulu_page.page_edit_form.details', '/pages/:id/details'))
            ->setResourceKey('pages')
            ->setFormKey('pages')
            ->setPreviewCondition('nodeType == 1')
            ->getView();

        $this->assertSame('nodeType == 1', $view->getOption('previewCondition'));
    }

    public function testBuildFormWithParent()
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption()
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }

    public function testBuildFormWithLocales()
    {
        $view = (new PreviewFormViewBuilder('sulu_role.add_form', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();

        $this->assertSame(['de', 'en', 'nl', 'fr'], $view->getOption('locales'));
    }

    public function testBuildFormWithLocalesWithoutLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $view = (new PreviewFormViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addLocales(['de', 'en'])
            ->addLocales(['nl', 'fr'])
            ->getView();
    }

    public function testBuildFormWithoutLocalesWithLocalePlaceholder()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('":locale"');

        $view = (new PreviewFormViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->getView();
    }

    public function testBuildFormWithRedirectToItself()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('"editView"');

        $view = (new PreviewFormViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setEditView('sulu_role.list')
            ->getView();
    }
}
