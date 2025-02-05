<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\UserInterface\Controller\Admin;

use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CopyLocalePageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\Message\RemovePageMessage;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal this class should not be instated by a project
 *           Use instead a request or response listener to
 *           extend the endpoints behaviours
 */
final class PageController
{
    use HandleTrait;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var ContentManagerInterface
     */
    private $contentManager;

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $listBuilderFactory;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        MessageBusInterface $messageBus,
        NormalizerInterface $normalizer,
        ContentManagerInterface $contentManager,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        RestHelperInterface $restHelper
    ) {
        $this->pageRepository = $pageRepository;
        $this->messageBus = $messageBus;
        $this->normalizer = $normalizer;

        // TODO controller should not need more then Repository, MessageBus, Serializer
        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->listBuilderFactory = $listBuilderFactory;
        $this->restHelper = $restHelper;
        $this->contentManager = $contentManager;
    }

    public function cgetAction(Request $request): Response
    {
        // TODO this should be PageRepository::findFlatBy / ::countFlatBy methods
        //      but first we would need to avoid that the restHelper requires the request.
        //
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(PageInterface::RESOURCE_KEY);
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(PageInterface::class);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['ghostLocale']);
        $listBuilder->setParameter('locale', $request->query->get('locale'));
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            PageInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse($this->normalizer->normalize(
            $listRepresentation->toArray(), // TODO maybe a listener should automatically do that for `sulu_admin` context
            'json',
            ['sulu_admin' => true, 'sulu_admin_page' => true, 'sulu_admin_page_list' => true],
        ));
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $dimensionAttributes = [
            'locale' => $request->query->getString('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $page = $this->pageRepository->getOneBy(
            \array_merge(
                [
                    'uuid' => $id,
                    'loadGhost' => true,
                ],
                $dimensionAttributes,
            ),
            [
                PageRepositoryInterface::GROUP_SELECT_PAGE_ADMIN => true,
            ]
        );

        // TODO the `$page` should just be serialized
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($page, $dimensionAttributes);
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent, // TODO this should just be the page entity see comment above
            'json',
            ['sulu_admin' => true, 'sulu_admin_page' => true, 'sulu_admin_page_content' => true],
        ));
    }

    public function postAction(Request $request): Response
    {
        $message = new CreatePageMessage($this->getData($request));

        /** @see Sulu\Page\Application\MessageHandler\CreatePageMessageHandler */
        /** @var PageInterface $page */
        $page = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        $uuid = $page->getUuid();

        $this->handleAction($request, $uuid);

        $response = $this->getAction($request, $uuid);

        return $response->setStatusCode(201);
    }

    public function putAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $message = new ModifyPageMessage(['uuid' => $id], $this->getData($request));
        /** @see Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function deleteAction(Request $request, string $id): Response // TODO route should be a uuid
    {
        $message = new RemovePageMessage(['uuid' => $id]);
        /** @see Sulu\Page\Application\MessageHandler\RemovePageMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new Response('', 204);
    }

    /**
     * @return mixed[]
     */
    private function getData(Request $request): array
    {
        return \array_replace(
            $request->request->all(),
            [
                'locale' => $this->getLocale($request),
            ]
        );
    }

    private function getLocale(Request $request): string
    {
        return $request->query->getString('locale', $request->getLocale());
    }

    private function handleAction(Request $request, string $uuid): ?PageInterface // @phpstan-ignore-line
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return null;
        }

        if ('copy-locale' === $action) {
            $message = new CopyLocalePageMessage(
                ['uuid' => $uuid],
                (string) $request->query->get('src'),
                (string) $request->query->get('dest')
            );

            /** @see Sulu\Page\Application\MessageHandler\CopyLocalePageMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } else {
            $message = new ApplyWorkflowTransitionPageMessage(['uuid' => $uuid], $this->getLocale($request), $action);

            /** @see Sulu\Page\Application\MessageHandler\ApplyWorkflowTransitionPageMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
    }
}
