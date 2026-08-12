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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\Admin\View\View;
use Sulu\Bundle\AdminBundle\Admin\View\ViewRegistry;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGenerator;
use Sulu\Bundle\AdminBundle\Admin\View\ViewUrlGeneratorInterface;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewParameterNotFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ViewUrlGeneratorTest extends TestCase
{
    use ProphecyTrait;

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

    public function setUp(): void
    {
        $this->urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $this->viewRegistry = $this->prophesize(ViewRegistry::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
    }

    private function createViewUrlGenerator(bool $withRequestStack = true): ViewUrlGenerator
    {
        return new ViewUrlGenerator(
            $this->urlGenerator->reveal(),
            $this->viewRegistry->reveal(),
            $withRequestStack ? $this->requestStack->reveal() : null
        );
    }

    public function testGenerate(): void
    {
        $view = new View('sulu_contact.contact_edit_form.details', '/contacts/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_contact.contact_edit_form.details')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $this->urlGenerator->generate('sulu_admin', [], ViewUrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/admin/');

        $viewUrlGenerator = $this->createViewUrlGenerator();

        $this->assertSame(
            '/admin/#/contacts/1/details',
            $viewUrlGenerator->generate('sulu_contact.contact_edit_form.details', ['id' => 1])
        );
    }

    public function testGenerateWithoutPlaceholders(): void
    {
        $view = new View('sulu_contact.contacts', '/contacts', 'list');
        $this->viewRegistry->findViewByName('sulu_contact.contacts')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $this->urlGenerator->generate('sulu_admin', [], ViewUrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/admin/');

        $viewUrlGenerator = $this->createViewUrlGenerator();

        $this->assertSame('/admin/#/contacts', $viewUrlGenerator->generate('sulu_contact.contacts'));
    }

    public function testGenerateUrlEncodesParameters(): void
    {
        $view = new View('sulu_contact.contact_edit_form.details', '/contacts/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_contact.contact_edit_form.details')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $this->urlGenerator->generate('sulu_admin', [], ViewUrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/admin/');

        $viewUrlGenerator = $this->createViewUrlGenerator();

        $this->assertSame(
            '/admin/#/contacts/john%2Fdoe/details',
            $viewUrlGenerator->generate('sulu_contact.contact_edit_form.details', ['id' => 'john/doe'])
        );
    }

    public function testGenerateWithAbsoluteUrl(): void
    {
        $view = new View('sulu_contact.contact_edit_form.details', '/contacts/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_contact.contact_edit_form.details')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $this->urlGenerator->generate('sulu_admin', [], ViewUrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.org/admin/');

        $viewUrlGenerator = $this->createViewUrlGenerator();

        $this->assertSame(
            'https://example.org/admin/#/contacts/1/details',
            $viewUrlGenerator->generate(
                'sulu_contact.contact_edit_form.details',
                ['id' => 1],
                ViewUrlGeneratorInterface::ABSOLUTE_URL
            )
        );
    }

    public function testGenerateThrowsExceptionForMissingParameter(): void
    {
        $this->expectException(ViewParameterNotFoundException::class);

        $view = new View('sulu_contact.contact_edit_form.details', '/contacts/:id/details', 'form');
        $this->viewRegistry->findViewByName('sulu_contact.contact_edit_form.details')->willReturn($view);
        $this->requestStack->getCurrentRequest()->willReturn(null);

        $viewUrlGenerator = $this->createViewUrlGenerator();
        $viewUrlGenerator->generate('sulu_contact.contact_edit_form.details');
    }

    public function testGeneratePropagatesViewNotFoundException(): void
    {
        $this->expectException(ViewNotFoundException::class);

        $this->viewRegistry->findViewByName('sulu_contact.not_existing')
            ->willThrow(new ViewNotFoundException('sulu_contact.not_existing'));

        $viewUrlGenerator = $this->createViewUrlGenerator(false);
        $viewUrlGenerator->generate('sulu_contact.not_existing');
    }

    public function testGenerateFillsWebspaceAndLocaleFromRequest(): void
    {
        $view = new View('sulu_page.page_edit_form', '/webspaces/:webspace/pages/:locale/:id', 'page');
        $this->viewRegistry->findViewByName('sulu_page.page_edit_form')->willReturn($view);

        $request = Request::create('/', 'GET', ['locale' => 'de']);
        $request->attributes = new ParameterBag(['webspace' => 'sulu']);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->urlGenerator->generate('sulu_admin', [], ViewUrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/admin/');

        $viewUrlGenerator = $this->createViewUrlGenerator();

        $this->assertSame(
            '/admin/#/webspaces/sulu/pages/de/3',
            $viewUrlGenerator->generate('sulu_page.page_edit_form', ['id' => 3])
        );
    }

    public function testGenerateFallsBackToRequestLocaleWhenQueryLocaleMissing(): void
    {
        $view = new View('sulu_page.pages', '/pages/:locale', 'list');
        $this->viewRegistry->findViewByName('sulu_page.pages')->willReturn($view);

        $request = new Request();
        $request->setLocale('en');
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->urlGenerator->generate('sulu_admin', [], ViewUrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/admin/');

        $viewUrlGenerator = $this->createViewUrlGenerator();

        $this->assertSame('/admin/#/pages/en', $viewUrlGenerator->generate('sulu_page.pages'));
    }

    public function testGenerateExplicitParameterOverridesRequestDefault(): void
    {
        $view = new View('sulu_page.pages', '/pages/:locale', 'list');
        $this->viewRegistry->findViewByName('sulu_page.pages')->willReturn($view);

        $request = Request::create('/', 'GET', ['locale' => 'de']);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->urlGenerator->generate('sulu_admin', [], ViewUrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/admin/');

        $viewUrlGenerator = $this->createViewUrlGenerator();

        $this->assertSame(
            '/admin/#/pages/en',
            $viewUrlGenerator->generate('sulu_page.pages', ['locale' => 'en'])
        );
    }

    public function testGenerateWithoutRequestStackThrowsForRequiredParameter(): void
    {
        $this->expectException(ViewParameterNotFoundException::class);

        $view = new View('sulu_page.pages', '/pages/:locale', 'list');
        $this->viewRegistry->findViewByName('sulu_page.pages')->willReturn($view);

        $viewUrlGenerator = $this->createViewUrlGenerator(false);
        $viewUrlGenerator->generate('sulu_page.pages');
    }
}
