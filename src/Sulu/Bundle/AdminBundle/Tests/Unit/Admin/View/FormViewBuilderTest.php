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
use Sulu\Bundle\AdminBundle\Admin\View\Badge;
use Sulu\Bundle\AdminBundle\Admin\View\FormViewBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;

class FormViewBuilderTest extends TestCase
{
    public function testBuildFormViewWithClone()
    {
        $viewBuilder = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles');

        $this->assertNotSame($viewBuilder->getView(), $viewBuilder->getView());
    }

    public function testBuildFormViewWithoutResourceKey()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('/"setResourceKey"/');

        $view = (new FormViewBuilder('sulu_category.edit_form.details', '/details'))
            ->getView();
    }

    public function provideBuildFormView()
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
                null,
                null,
                null,
                ['test1' => 'value1'],
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
                null,
                null,
                null,
                null,
            ],
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
                ['webspace'],
                ['webspaceKey' => 'webspace'],
                true,
                null,
            ],
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
                ['webspace', 'id' => 'active'],
                ['webspace'],
                false,
                null,
            ],
        ];
    }

    /**
     * @dataProvider provideBuildFormView
     */
    public function testBuildFormView(
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
        ?array $routerAttributesToBackView,
        ?array $routerAttributesToFormMetadata,
        ?bool $titleVisible,
        ?array $requestParameters
    ) {
        $viewBuilder = (new FormViewBuilder($name, $path))
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

        if ($routerAttributesToBackView) {
            $viewBuilder->addRouterAttributesToBackView($routerAttributesToBackView);
        }

        if ($routerAttributesToFormMetadata) {
            $viewBuilder->addRouterAttributesToFormMetadata($routerAttributesToFormMetadata);
        }

        if (null !== $titleVisible) {
            $viewBuilder->setTitleVisible($titleVisible);
        }

        if ($requestParameters) {
            $viewBuilder->addRequestParameters($requestParameters);
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
        $this->assertSame($routerAttributesToBackView, $view->getOption('routerAttributesToBackView'));
        $this->assertSame($routerAttributesToFormMetadata, $view->getOption('routerAttributesToFormMetadata'));
        $this->assertSame($titleVisible, $view->getOption('titleVisible'));
        $this->assertSame($requestParameters, $view->getOption('requestParameters'));
        $this->assertNull($view->getParent());
        $this->assertSame('sulu_admin.form', $view->getType());
    }

    public function testBuildFormWithToolbarActions()
    {
        $saveToolbarAction = new ToolbarAction('sulu_admin.save');
        $typesToolbarAction = new ToolbarAction('sulu_admin.types');
        $deleteToolbarAction = new ToolbarAction('sulu_admin.delete');

        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
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
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
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
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
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
        $view = (new FormViewBuilder('sulu_security.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setIdQueryParameter('contactId')
            ->getView();

        $this->assertSame(
            'contactId',
            $view->getOption('idQueryParameter')
        );
    }

    public function testBuildFormWithParent()
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setParent('sulu_admin.test')
            ->getView();

        $this->assertSame('sulu_admin.test', $view->getParent());
    }

    public function testBuildFormWithOption()
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setOption('resourceKey', 'test')
            ->getView();

        $this->assertSame('test', $view->getOption('resourceKey'));
    }

    public function testBuildFormWithLocales()
    {
        $view = (new FormViewBuilder('sulu_role.add_form', '/roles/:locale'))
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

        $view = (new FormViewBuilder('sulu_role.list', '/roles'))
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

        $view = (new FormViewBuilder('sulu_role.list', '/roles/:locale'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->getView();
    }

    public function testBuildFormWithRedirectToItself()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageRegExp('"editView"');

        $view = (new FormViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->setEditView('sulu_role.list')
            ->getView();
    }

    public function testBuildAddTabBadge()
    {
        $fooBadge = new Badge('sulu_foo.get_foo_badge');
        $barBadge = new Badge('sulu_bar.get_bar_badge');
        $bazBadge = (new Badge('sulu_baz.get_baz_badge', '/total'))
            ->setVisibleCondition('text != 0')
            ->addAttributesToRequest([
                'limit' => 0,
                'entityClass' => 'Sulu\Bundle\BazBundle\Entity\Baz',
            ])
            ->addRouterAttributesToRequest([
                'locale',
                'id' => 'entityId',
            ]);

        $view = (new FormViewBuilder('sulu_role.list', '/roles'))
            ->setResourceKey('roles')
            ->setFormKey('roles')
            ->addTabBadge($fooBadge)
            ->addTabBadge($barBadge, 'abc')
            ->addTabBadge($bazBadge, 'abc')
            ->getView();

        $this->assertEquals(
            [
                [
                    'routeName' => 'sulu_foo.get_foo_badge',
                    'dataPath' => null,
                    'visibleCondition' => null,
                    'attributesToRequest' => [],
                    'routerAttributesToRequest' => [],
                ],
                'abc' => [
                    'routeName' => 'sulu_baz.get_baz_badge',
                    'dataPath' => '/total',
                    'visibleCondition' => 'text != 0',
                    'attributesToRequest' => [
                        'limit' => 0,
                        'entityClass' => 'Sulu\Bundle\BazBundle\Entity\Baz',
                    ],
                    'routerAttributesToRequest' => [
                        'locale',
                        'id' => 'entityId',
                    ],
                ],
            ],
            $view->getOption('tabBadges')
        );
    }
}
