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

namespace Sulu\CustomUrl\UserInterface\Controller\Admin;

use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelper;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\CustomUrl\Application\Messages\RemoveCustomUrlRoutesMessage;
use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

class CustomUrlRouteController implements SecuredControllerInterface
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private MessageBusInterface $messageBus,
        private NormalizerInterface $normalizer,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelper $restHelper,
        private RequestStack $requestStack,
    ) {
    }

    public function cgetAction(string $webspace, string $id, Request $request): Response
    {
        // Verify the custom URL exists and belongs to the webspace
        $customUrl = $this->customUrlRepository->findOneBy(['uuid' => $id, 'webspace' => $webspace]);
        if (null === $customUrl) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('custom_url_routes');

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(CustomUrlRouteInterface::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
        $listBuilder->setIdField($fieldDescriptors['id']);

        // Filter by custom URL and only show history routes
        $listBuilder->where($fieldDescriptors['customUrl'], $id);
        $listBuilder->where($fieldDescriptors['history'], '1');

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            'custom_url_routes',
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse($this->normalizer->normalize(
            $listRepresentation->toArray(),
            'json',
            ['sulu_admin' => true],
        ));
    }

    public function cdeleteAction(string $webspace, string $id, Request $request): Response
    {
        $routeIds = \array_filter(\explode(',', $request->query->getString('ids', '')));

        try {
            $this->messageBus->dispatch(new Envelope(
                new RemoveCustomUrlRoutesMessage($id, $webspace, $routeIds),
                [new EnableFlushStamp()],
            ));
        } catch (\Symfony\Component\Security\Core\Exception\AccessDeniedException $e) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

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
        return $request->query->get('locale', null);
    }
}
