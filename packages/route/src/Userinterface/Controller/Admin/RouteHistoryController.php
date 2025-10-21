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

use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Route\Application\Message\RemoveRouteHistoryMessage;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class should not be instated by a project
 *           Use instead a request or response listener to
 *           extend the endpoints behaviours
 */
final class RouteHistoryController
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus, // @phpstan-ignore property.onlyWritten
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
    ) {
        // TODO controller should not need more then Repository, MessageBus, Serializer
    }

    public function cgetAction(Request $request): Response
    {
        $resourceKey = $request->query->get('resourceKey');
        $resourceId = $request->query->get('resourceId');
        $locale = $request->query->get('locale');

        if (!$resourceKey || !$resourceId || !$locale) {
            return new JsonResponse(['message' => 'resourceKey, resourceId and locale are required query parameters.'], 400);
        }

        $routeHistoryResourceId = $resourceKey . '::' . $resourceId;
        $routeHistoryResourceKey = Route::HISTORY_RESOURCE_KEY;

        /** @var DoctrineFieldDescriptor[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(Route::HISTORY_RESOURCE_KEY);

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($fieldDescriptors['id']->getEntityName());
        $listBuilder->where($fieldDescriptors['resourceKey'], $routeHistoryResourceKey);
        $listBuilder->where($fieldDescriptors['resourceId'], $routeHistoryResourceId);
        $listBuilder->where($fieldDescriptors['locale'], $locale);

        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $result = $listBuilder->execute();
        $listRepresentation = new PaginatedRepresentation(
            $result,
            Route::HISTORY_RESOURCE_KEY,
            $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse($listRepresentation->toArray());
    }

    public function cdeleteAction(Request $request): Response
    {
        /** @var numeric-string[] $ids */
        $ids = \array_filter(\explode(',', $request->query->getString('ids')), function(string $id) {
            return $id && \is_numeric($id);
        });

        foreach ($ids as $id) {
            $message = new RemoveRouteHistoryMessage(['id' => (int) $id]);
            $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }

        return new JsonResponse(null, 204);
    }
}
