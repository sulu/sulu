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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelper;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\CustomUrl\Application\Messages\CreateCustomUrlMessage;
use Sulu\CustomUrl\Application\Messages\ModifyCustomUrlMessage;
use Sulu\CustomUrl\Application\Messages\RemoveCustomUrlMessage;
use Sulu\CustomUrl\Domain\Exception\CustomUrlAlreadyExistsException;
use Sulu\CustomUrl\Domain\Exception\MismatchingDomainPartException;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

class CustomUrlController implements SecuredControllerInterface
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private RequestStack $requestStack,
        private CustomUrlRepositoryInterface $customUrlRepository,
        private NormalizerInterface $normalizer,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelper $restHelper,
    ) {
        $this->messageBus = $messageBus;
    }

    public function cgetAction(string $webspace, Request $request): Response
    {
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(CustomUrlInterface::RESOURCE_KEY);

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(CustomUrlInterface::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->addSelectField($fieldDescriptors['publishedState']);
        $listBuilder->setParameter('locale', $request->query->get('locale'));
        $listBuilder->where($fieldDescriptors['webspace'], $webspace);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            CustomUrlInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse($this->normalizer->normalize(
            $listRepresentation->toArray(),
            'json',
            ['sulu_admin' => true, 'sulu_admin_custom_url' => true, 'sulu_admin_custom_url_list' => true],
        ));
    }

    public function getAction(string $webspace, string $id, Request $request): Response
    {
        $customUrl = $this->customUrlRepository->findOneBy(['uuid' => $id, 'webspace' => $webspace]);
        if (null === $customUrl) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->normalizer->normalize(
            $customUrl,
            'json',
            ['sulu_admin' => true, 'sulu_admin_custom_url' => true, 'sulu_admin_custom_url_content' => true],
        ));
    }

    public function postAction(string $webspace, Request $request): Response
    {
        $requestData = $request->request->all();
        try {
            /** @see \Sulu\CustomUrl\Application\MessageHandler\CreateCustomUrlMessageHandler */
            /** @var CustomUrlInterface $customUrl */
            $customUrl = $this->handle(new Envelope(
                new CreateCustomUrlMessage(
                    webspaceKey: $webspace,
                    data: $requestData,
                ),
                [new EnableFlushStamp()],
            ));
        } catch (CustomUrlAlreadyExistsException $e) {
            return $this->createErrorResponse(\sprintf('Title "%s" already in use', $e->getTitle()), 9001);
        } catch (UniqueConstraintViolationException $e) {
            // Path conflict - routes have unique constraint on path
            return new JsonResponse(null, Response::HTTP_CONFLICT);
        } catch (MismatchingDomainPartException $e) {
            return $this->createErrorResponse($e->getMessage(), $e->getCode());
        }

        return new JsonResponse($this->normalizer->normalize(
            $customUrl,
            'json',
            ['sulu_admin' => true, 'sulu_admin_custom_url' => true, 'sulu_admin_custom_url_content' => true],
        ));
    }

    public function putAction(string $webspace, string $id, Request $request): Response
    {
        $requestData = $request->request->all();
        unset($requestData['creator'], $requestData['changer'], $requestData['created'], $requestData['updated']);

        try {
            /** @see \Sulu\CustomUrl\Application\MessageHandler\ModifyCustomUrlMessageHandler */
            /** @var CustomUrlInterface $customUrl */
            $customUrl = $this->handle(new Envelope(
                new ModifyCustomUrlMessage(
                    uuid: $id,
                    webspaceKey: $webspace,
                    data: $requestData,
                ),
                [new EnableFlushStamp()],
            ));
        } catch (CustomUrlAlreadyExistsException $e) {
            return $this->createErrorResponse(\sprintf('Title "%s" already in use', $e->getTitle()), 9001);
        } catch (UniqueConstraintViolationException $e) {
            // Path conflict - routes have unique constraint on path
            return new JsonResponse(null, Response::HTTP_CONFLICT);
        } catch (MismatchingDomainPartException $e) {
            return $this->createErrorResponse($e->getMessage(), $e->getCode());
        }

        return new JsonResponse($this->normalizer->normalize(
            $customUrl,
            'json',
            ['sulu_admin' => true, 'sulu_admin_custom_url' => true, 'sulu_admin_custom_url_content' => true],
        ));
    }

    public function deleteAction(string $webspace, string $id): Response
    {
        /** @see \Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlMessageHandler */
        /** @var CustomUrlInterface $customUrl */
        $customUrl = $this->handle(new Envelope(
            new RemoveCustomUrlMessage(uuid: $id, webspaceKey: $webspace),
            [new EnableFlushStamp()],
        ));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    public function cdeleteAction(string $webspace, Request $request): Response
    {
        $ids = \array_filter(\explode(',', $request->query->getString('ids', '')));

        // TODO: bulk delete should either be more efficient or just fall back to calling the delete endpoint in code
        foreach ($ids as $id) {
            /** @see \Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlMessageHandler */
            $this->handle(new Envelope(
                new RemoveCustomUrlMessage(uuid: $id, webspaceKey: $webspace),
                [new EnableFlushStamp()],
            ));
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function createErrorResponse(string $errorMessage, int $errorCode): JsonResponse
    {
        return new JsonResponse([
            'code' => $errorCode,
            'message' => $errorMessage,
        ], status: Response::HTTP_BAD_REQUEST);
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
