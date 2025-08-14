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
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
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

        $parameters = $this->resolveSuluParameters($object, 'json' === $requestFormat);

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
    protected function resolveSuluParameters(DimensionContentInterface $object, bool $normalize): array
    {
        return $this->container->get('sulu_content.content_resolver')->resolve($object); // TODO should the resolver already normalize the data based on metadata inside the template (serialization group)
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

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['sulu_content.content_resolver'] = ContentResolverInterface::class;
        $services['sulu_http_cache.cache_lifetime.enhancer'] = CacheLifetimeEnhancerInterface::class;

        return $services;
    }
}
