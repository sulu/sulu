<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\UserInterface\Controller\Admin;

use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRouteRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

class CustomUrlRouteController implements SecuredControllerInterface
{
    private static string $relationName = 'custom_url_routes';

    public function __construct(
        private RequestStack $requestStack,
        private CustomUrlRouteRepositoryInterface $customUrlRouteRepository,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function cgetAction(string $webspace, string $id, Request $request): Response
    {
        $result = new CollectionRepresentation(
            $this->customUrlRouteRepository->findHistoryRoutes($id),
            self::$relationName,
        );

        return new JsonResponse($this->normalizer->normalize(
            $result->toArray(),
            'json',
            ['sulu_admin' => true, 'sulu_admin_custom_url_route' => true, 'sulu_admin_custom_url_route_list' => true],
        ));
    }

    public function cdeleteAction(string $webspace, Request $request): Response
    {
        $ids = \explode(',', $request->attributes->getString('ids', ''));

        $this->customUrlRouteRepository->deleteAll(\array_filter($ids), $webspace);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    public function getSecurityContext(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request, 'Unable to get from request stack');

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->attributes->getString('webspace'));
    }

    public function getLocale(Request $request): ?string
    {
        return null;
    }
}
