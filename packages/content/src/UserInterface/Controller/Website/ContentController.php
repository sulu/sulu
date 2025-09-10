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

namespace Sulu\Content\UserInterface\Controller\Website;

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancerInterface;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * This controller is commonly extended when additional data needs to be loaded for a specific template.
 * The `resolveSuluParameters` method provides access to all managed content, allowing you to extend
 * it with additional parameters.
 *
 * Example usage:
 *
 *      <code>
 *      class ExampleContentController extends ContentController
 *      {
 *          protected function resolveSuluParameters(DimensionContentInterface $object, bool $normalize): array
 *          {
 *              $parameters = parent::resolveSuluParameters($object, $normalize);
 *              $parameters['custom_key'] = 'custom_variable'; // add whatever you want to add here
 *
 *              return $parameters;
 *          }
 *      }
 *      </code>
 *
 * @template T of DimensionContentInterface
 */
class ContentController extends AbstractController
{
    /**
     * @param T $object
     */
    public function indexAction(
        Request $request,
        DimensionContentInterface $object,
        string $view, // TODO maybe inject metadata where we also get the cachelifetime from
        bool $preview = false,
        bool $partial = false,
    ): Response {
        $requestFormat = $request->getRequestFormat() ?? 'html';

        $webspaceKey = $this->getSuluWebspaceKey($request);

        $parameters = $this->resolveSuluParameters($object, $webspaceKey, 'json' === $requestFormat);

        if ('json' === $requestFormat) {
            $parameters = []; // TODO normalize for JSON response here or inside the resolver already, no headless support yet

            $response = new JsonResponse($parameters);
        } else {
            $response = new Response($this->renderSuluView($view, $requestFormat, $parameters, $preview, $partial));
        }

        $this->enhanceSuluCacheLifeTime($response);

        return $response;
    }

    /**
     * @param T $object
     *
     * @return array<string, mixed>
     */
    protected function resolveSuluParameters(DimensionContentInterface $object, string $webspaceKey, bool $normalize): array
    {
        $data = $this->container->get('sulu_content.content_resolver')->resolve($object); // TODO should the resolver already normalize the data based on metadata inside the template (serialization group)
        $data['localizations'] = $this->resolveSuluLocalizations($object, $webspaceKey);

        return $data;
    }

    protected function enhanceSuluCacheLifeTime(Response $response): void
    {
        $this->container->get('sulu_http_cache.cache_lifetime.enhancer')->enhance($response);
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @throws NotAcceptableHttpException
     */
    protected function renderSuluView(
        string $view,
        string $requestFormat, // TODO maybe we should avoid this and resolve it before
        array $parameters,
        bool $preview,
        bool $partial,
    ): string {
        $viewTemplate = $view . '.' . $requestFormat . '.twig';

        if (!$this->container->get('twig')->getLoader()->exists($viewTemplate)) {
            throw new NotAcceptableHttpException(\sprintf('Page does not exist in "%s" format.', $requestFormat));
        }

        if ($partial) {
            return $this->renderBlockView($viewTemplate, 'content', $parameters);
        } elseif ($preview) {
            $parameters['previewParentTemplate'] = $viewTemplate;
            $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;
            $viewTemplate = '@SuluWebsite/Preview/preview.html.twig';
        }

        return $this->renderView($viewTemplate, $parameters);
    }

    private function getSuluWebspaceKey(Request $request): string
    {
        $suluAttribute = $request->attributes->get('_sulu');

        \assert($suluAttribute instanceof RequestAttributes, 'The "_sulu" request attribute must be of type ' . RequestAttributes::class . ', but got: ' . \get_debug_type($suluAttribute));
        $attributes = $suluAttribute->getAttributes();
        $webspace = $attributes['webspace'];
        \assert($webspace instanceof Webspace, 'The "webspace" request attribute must be of type ' . Webspace::class . ', but got: ' . \get_debug_type($webspace));

        return $webspace->getKey();
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['sulu_content.content_resolver'] = ContentResolverInterface::class;
        $services['sulu_http_cache.cache_lifetime.enhancer'] = CacheLifetimeEnhancerInterface::class;

        $services['sulu_route.route_repository'] = RouteRepositoryInterface::class;
        $services['sulu_route.route_generator'] = RouteGeneratorInterface::class;
        $services['sulu_core.webspace.webspace_manager'] = WebspaceManagerInterface::class;

        return $services;
    }

    /**
     * TODO maybe we move this into a content website resolver service, depending on route localization switcher service.
     *      see https://github.com/sulu/sulu/issues/8175.
     *
     * @param T $object
     *
     * @return array<string, array{
     *      url: string,
     *      locale: string,
     *      alternate: bool
     * }>
     */
    private function resolveSuluLocalizations(DimensionContentInterface $object, string $webspaceKey): array
    {
        if (!$object instanceof RoutableInterface) {
            return [];
        }

        $routes = [];
        foreach ($this->container->get('sulu_route.route_repository')->findBy([
            'resourceKey' => $object::getResourceKey(),
            'resourceId' => (string) $object->getResource()->getId(),
            'locales' => $object->getAvailableLocales() ?? [],
        ]) as $route) {
            $routes[] = $route;
        }

        $localizations = [];
        foreach ($routes as $route) {
            $locale = $route->getLocale();
            $localizations[$locale] = [
                'url' => $this->container->get('sulu_route.route_generator')->generate(
                    $route->getSlug(),
                    $route->getLocale(),
                    $webspaceKey,
                ),
                'locale' => $locale,
                'alternate' => true,
            ];
        }

        $webspaceLocales = $this->container->get('sulu_core.webspace.webspace_manager')
            ->getWebspaceCollection()
            ->getWebspace($webspaceKey)
            ?->getAllLocalizations() ?? [];

        foreach ($webspaceLocales as $webspaceLocale) {
            $locale = $webspaceLocale->getLocale(Localization::DASH);
            if (\array_key_exists($locale, $localizations)) {
                continue;
            }

            $localizations[$locale] = [
                'url' => $this->container->get('sulu_route.route_generator')->generate(
                    '/',
                    $locale,
                    $webspaceKey,
                ),
                'locale' => $locale,
                'alternate' => false,
            ];
        }

        return $localizations;
    }
}
