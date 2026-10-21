<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Userinterface\Controller\Admin;

use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class RouteUrlController
{
    public function __construct(
        private RouteRepositoryInterface $routeRepository,
        private RouteGeneratorInterface $routeGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $resourceKey = $request->query->getString('resourceKey');
        $resourceId = $request->query->getString('resourceId');
        $locale = $request->query->getString('locale');
        $webspace = $request->query->getString('webspace');

        if ('' === $resourceKey || '' === $resourceId || '' === $locale || '' === $webspace) {
            throw new BadRequestHttpException('The parameters "resourceKey", "resourceId", "locale" and "webspace" are required.');
        }

        // the saved route is used, so that the url always matches the persisted state of the resource
        $route = $this->routeRepository->findOneBy([
            'resourceKey' => $resourceKey,
            'resourceId' => $resourceId,
            'locale' => $locale,
        ]);

        if (!$route) {
            throw new NotFoundHttpException(\sprintf('No route found for "%s" with id "%s" and locale "%s".', $resourceKey, $resourceId, $locale));
        }

        try {
            $url = $this->routeGenerator->generate($route->getSlug(), $locale, $webspace, UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (\RuntimeException $exception) {
            throw new NotFoundHttpException(\sprintf('The webspace "%s" has no url for locale "%s".', $webspace, $locale), $exception);
        }

        return new RedirectResponse($url);
    }
}
