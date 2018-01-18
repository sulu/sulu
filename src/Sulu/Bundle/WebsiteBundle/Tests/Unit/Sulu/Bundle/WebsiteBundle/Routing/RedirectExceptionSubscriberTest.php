<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Routing;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\EventListener\RedirectExceptionSubscriber;
use Sulu\Bundle\WebsiteBundle\Locale\DefaultLocaleProviderInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Route;

class RedirectExceptionSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestMatcherInterface
     */
    private $router;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var DefaultLocaleProviderInterface
     */
    private $defaultLocaleProvider;

    /**
     * @var ReplacerInterface
     */
    private $urlReplacer;

    /**
     * @var RedirectExceptionSubscriber
     */
    private $exceptionListener;

    /**
     * @var RequestAttributes
     */
    private $attributes;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var GetResponseForExceptionEvent
     */
    private $event;

    protected function setUp()
    {
        $this->router = $this->prophesize(RequestMatcherInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $this->urlReplacer = $this->prophesize(ReplacerInterface::class);

        $this->exceptionListener = new RedirectExceptionSubscriber(
            $this->router->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->defaultLocaleProvider->reveal(),
            $this->urlReplacer->reveal()
        );

        $this->attributes = $this->prophesize(RequestAttributes::class);
        $this->request = $this->prophesize(Request::class);
        $this->request->getUri()->willReturn('sulu.lo');
        $this->request->getSchemeAndHttpHost()->willReturn('http://sulu.lo');
        $this->request->reveal()->attributes = new ParameterBag(['_sulu' => $this->attributes->reveal()]);
        $this->event = $this->prophesize(GetResponseForExceptionEvent::class);
        $this->event->getRequest()->willReturn($this->request->reveal());
        $this->event->getException()->willReturn(new NotFoundHttpException());
    }

    public function testRedirectTrailingSlash()
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test/');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return '/de/test' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event->reveal());
    }

    public function testRedirectSlashToHomepage()
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn(null);

        $this->request->getPathInfo()->willReturn('//');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return '/' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event->reveal());
    }

    public function testRedirectDoubleSlashToHomepage()
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('//');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn(null);

        $this->request->getPathInfo()->willReturn('///');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    echo '"' . $response->getTargetUrl() . '"';

                    return '/' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event->reveal());
    }

    public function testRedirectTrailingHtml()
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test.html');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return '/de/test' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event->reveal());
    }

    public function testRedirectJson()
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test.json');
        $this->request->getRequestFormat()->willReturn('json');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event->reveal());
    }

    public function testRedirectTrailingHtmlNotExists()
    {
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->request->getPathInfo()->willReturn('/de/test.html');
        $this->request->getRequestFormat()->willReturn('html');

        $this->router->matchRequest(Argument::type(Request::class))->willThrow(new ResourceNotFoundException());
        $this->event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->exceptionListener->redirectTrailingSlashOrHtml($this->event->reveal());
    }

    public function testRedirectPartialMatchNoLocalization()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn(null);
        $this->attributes->getAttribute('portalUrl', null)->willReturn(null);

        $localization = new Localization();
        $localization->setLanguage('de');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return 'http://sulu.lo' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }

    public function testRedirectPartialMatchNoLocalizationRedirect()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn(null);
        $this->attributes->getAttribute('portalUrl', null)->willReturn(null);

        $localization = new Localization();
        $localization->setLanguage('de');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return 'http://sulu.lo' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }

    public function testRedirectPartialMatchSlashOnly()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/de');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo/de/');

        $localization = new Localization();
        $localization->setLanguage('de');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return 'http://sulu.lo/de' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }

    public function testRedirectPartialMatch()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/{localization}');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');

        $localization = new Localization('de', 'at');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->urlReplacer->replaceCountry('sulu.lo/{localization}', 'at')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/{localization}');
        $this->urlReplacer->replaceLanguage('sulu.lo/{localization}', 'de')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/{localization}');
        $this->urlReplacer->replaceLocalization('sulu.lo/{localization}', 'de-at')
            ->shouldBeCalled()
            ->willReturn('sulu.lo/de-at');

        $this->router->matchRequest(
            Argument::that(
                function (Request $request) {
                    return 'http://sulu.lo/de-at' === $request->getUri();
                }
            )
        )->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return 'http://sulu.lo/de-at' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }

    public function testRedirectPartialMatchNotExists()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/{localization}');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');

        $localization = new Localization('de', 'at');
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->urlReplacer->replaceCountry('sulu.lo/{localization}', 'at')->shouldBeCalled()->willReturn(
            'sulu.lo/{localization}'
        );
        $this->urlReplacer->replaceLanguage('sulu.lo/{localization}', 'de')->shouldBeCalled()->willReturn(
            'sulu.lo/{localization}'
        );
        $this->urlReplacer->replaceLocalization('sulu.lo/{localization}', 'de-at')->shouldBeCalled()->willReturn(
            'sulu.lo/de-at'
        );

        $this->router->matchRequest(Argument::type(Request::class))->willThrow(new ResourceNotFoundException());
        $this->event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }

    public function testRedirectPartialMatchForRedirect()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->attributes->getAttribute('portal', null)->willReturn($portal);

        $this->attributes->getAttribute('localization', null)->willReturn(null);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_REDIRECT);
        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu-redirect.lo');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo');

        $this->defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));

        $this->urlReplacer->replaceCountry('sulu.lo', 'at')->shouldBeCalled()->willReturn('sulu.lo');
        $this->urlReplacer->replaceLanguage('sulu.lo', 'de')->shouldBeCalled()->willReturn('sulu.lo');
        $this->urlReplacer->replaceLocalization('sulu.lo', 'de-at')->shouldBeCalled()->willReturn('sulu.lo');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return 'http://sulu.lo' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }

    public function testRedirectPartialMatchWithDoubleSlashOnly()
    {
        $localization = new Localization('de', 'at');
        $this->attributes->getAttribute('localization', null)->willReturn($localization);
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('');
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');
        $this->attributes->getAttribute('redirect', null)->willReturn('sulu.lo/de-at');

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn('sulu.lo/de-at');

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) {
                    return 'http://sulu.lo/de-at' === $response->getTargetUrl();
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }

    public function provideResolveData()
    {
        return [
            ['http://sulu.lo/articles?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            [
                'http://sulu.lo/articles?foo=bar&bar=boo',
                'sulu.lo',
                'sulu.lo/en',
                'http://sulu.lo/en/articles?foo=bar&bar=boo',
            ],
            ['http://sulu.lo/articles/?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            [
                'http://sulu.lo/articles/?foo=bar&bar=boo',
                'sulu.lo',
                'sulu.lo/en',
                'http://sulu.lo/en/articles?foo=bar&bar=boo',
            ],
            ['http://sulu.lo/en/articles/?foo=bar', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar'],
            [
                'http://sulu.lo/en/articles/?foo=bar&bar=boo',
                'sulu.lo',
                null,
                'http://sulu.lo/en/articles?foo=bar&bar=boo',
            ],
            ['sulu.lo:8001/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en'],
            ['sulu.lo:8001/#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en#foobar'],
            ['sulu.lo:8001/articles#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en/articles#foobar'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['http://sulu.lo:8002/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8002/en'],
            ['http://sulu.lo/articles', 'sulu.lo/en', 'sulu.lo/de', 'http://sulu.lo/de/articles'],
            ['http://sulu.lo/events', 'sulu.lo/events', 'sulu.lo/events/de', 'http://sulu.lo/events/de', '/events'],
            [
                'http://sulu.lo/events/articles',
                'sulu.lo/events',
                'sulu.lo/events/de',
                'http://sulu.lo/events/de/articles',
                '/events',
            ],
        ];
    }

    /**
     * @dataProvider provideResolveData
     */
    public function testRedirectPartialMatchResolve(
        $requestUri,
        $portalUrl,
        $redirectUrl,
        $expectedTargetUrl,
        $prefix = ''
    ) {
        $this->request->getUri()->willReturn($requestUri);

        $localization = new Localization('de');
        $this->attributes->getAttribute('localization', null)->willReturn($localization);
        $this->defaultLocaleProvider->getDefaultLocale()->willReturn($localization);

        $this->attributes->getAttribute('resourceLocatorPrefix', null)->willReturn($prefix);
        $this->attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_PARTIAL);
        $this->attributes->getAttribute('portalUrl', null)->willReturn($portalUrl);
        $this->attributes->getAttribute('redirect', null)->willReturn($redirectUrl);

        $this->urlReplacer->replaceCountry(Argument::cetera())->shouldBeCalled()->willReturn($redirectUrl);
        $this->urlReplacer->replaceLanguage(Argument::cetera())->shouldBeCalled()->willReturn($redirectUrl);
        $this->urlReplacer->replaceLocalization(Argument::cetera())->shouldBeCalled()->willReturn($redirectUrl);

        $this->router->matchRequest(Argument::type(Request::class))->willReturn(
            $this->prophesize(Route::class)->reveal()
        );

        $this->event->setResponse(
            Argument::that(
                function (RedirectResponse $response) use ($expectedTargetUrl) {
                    return $response->getTargetUrl() === $expectedTargetUrl;
                }
            )
        )->shouldBeCalled();

        $this->exceptionListener->redirectPartialMatch($this->event->reveal());
    }
}
