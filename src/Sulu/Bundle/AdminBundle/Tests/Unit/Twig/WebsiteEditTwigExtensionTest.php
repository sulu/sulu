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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Twig\WebsiteEditTwigExtension;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class WebsiteEditTwigExtensionTest extends TestCase
{
    public function testRendersScriptWithRouteTarget(): void
    {
        $request = Request::create('/de/beispiel');
        $request->setLocale('de');
        $request->attributes->set('_sulu_route', new Route('pages', '123-abc', 'de', '/beispiel', 'example'));

        $this->assertSame(
            '<script src="/bundles/suluadmin/js/website-edit-button.js"'
            . ' data-url="/admin/website-edit?resourceKey=pages&amp;resourceId=123-abc&amp;locale=de&amp;webspace=example"'
            . ' data-label="Im Admin bearbeiten" data-cookie="sulu_admin_logged_in" defer></script>',
            $this->createExtension($request)->renderEditButton(),
        );
    }

    public function testRendersBareEndpointWithoutRoute(): void
    {
        $request = Request::create('/search');
        $request->setLocale('en');

        $this->assertStringContainsString(
            'data-url="/admin/website-edit"',
            $this->createExtension($request)->renderEditButton(),
        );
    }

    public function testOmitsMissingWebspace(): void
    {
        $request = Request::create('/en/article');
        $request->setLocale('en');
        $request->attributes->set('_sulu_route', new Route('articles', '456', 'en', '/article'));

        $this->assertStringContainsString(
            'data-url="/admin/website-edit?resourceKey=articles&amp;resourceId=456&amp;locale=en"',
            $this->createExtension($request)->renderEditButton(),
        );
    }

    public function testFallsBackToEnglishLabel(): void
    {
        $request = Request::create('/fr/page');
        $request->setLocale('fr');

        $this->assertStringContainsString(
            'data-label="Edit in admin"',
            $this->createExtension($request)->renderEditButton(),
        );
    }

    public function testEscapesAttributes(): void
    {
        $request = Request::create('/en/page');
        $request->setLocale('en');
        $request->attributes->set('_sulu_route', new Route('pages', '"><script>', 'en', '/page', 'example'));

        $output = $this->createExtension($request, '"><b>')->renderEditButton();

        $this->assertStringNotContainsString('"><script>', $output);
        $this->assertStringContainsString('data-label="&quot;&gt;&lt;b&gt;"', $output);
    }

    public function testRendersNothingDuringPreview(): void
    {
        $request = Request::create('/en/page');
        $request->attributes->set('preview', true);

        $this->assertSame('', $this->createExtension($request)->renderEditButton());
    }

    public function testRendersNothingWithoutRequest(): void
    {
        $extension = new WebsiteEditTwigExtension(new RequestStack(), new Translator('en'));

        $this->assertSame('', $extension->renderEditButton());
    }

    private function createExtension(Request $request, string $englishLabel = 'Edit in admin'): WebsiteEditTwigExtension
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['sulu_admin.edit_in_admin' => $englishLabel], 'en', 'admin');
        $translator->addResource('array', ['sulu_admin.edit_in_admin' => 'Im Admin bearbeiten'], 'de', 'admin');

        return new WebsiteEditTwigExtension($requestStack, $translator);
    }
}
