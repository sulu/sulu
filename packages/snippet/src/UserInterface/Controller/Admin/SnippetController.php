<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\UserInterface\Controller\Admin;

use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Snippet\Application\Message\ApplyWorkflowTransitionSnippetMessage;
use Sulu\Snippet\Application\Message\CopyLocaleSnippetMessage;
use Sulu\Snippet\Application\Message\CreateSnippetMessage;
use Sulu\Snippet\Application\Message\ModifySnippetMessage;
use Sulu\Snippet\Application\Message\RemoveSnippetMessage;
use Sulu\Snippet\Application\Message\RestoreSnippetVersionMessage;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;
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
 *
 * @phpstan-import-type SnippetAreaConfig from SnippetAreaCompilerPass
 */
final class SnippetController
{
    use HandleTrait;

    /**
     * @param SnippetAreaConfig $snippetAreas
     */
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
        MessageBusInterface $messageBus,
        private NormalizerInterface $normalizer,
        private ContentManagerInterface $contentManager,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private array $snippetAreas = []
    ) {
        $this->messageBus = $messageBus;
    }

    public function cgetAction(Request $request): Response
    {
        // TODO this should be SnippetRepository::findFlatBy / ::countFlatBy methods
        //      but first we would need to avoid that the restHelper requires the request.
        //
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(SnippetInterface::RESOURCE_KEY);
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(SnippetInterface::class);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['ghostLocale']);
        $listBuilder->addSelectField($fieldDescriptors['published']);
        $listBuilder->addSelectField($fieldDescriptors['publishedState']);
        $listBuilder->setParameter('locale', $request->query->get('locale'));
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $areasParam = $request->query->get('areas');
        if (null !== $areasParam) {
            $areas = \explode(',', (string) $areasParam);
            $types = [];
            foreach ($areas as $area) {
                if (!\array_key_exists($area, $this->snippetAreas)) {
                    continue;
                }

                $type = $this->snippetAreas[$area]['template'];
                if (empty($type)) {
                    continue;
                }
                $types[] = $type;
            }

            if (!empty($types)) {
                $listBuilder->in($fieldDescriptors['templateKey'], $types);
            }
        }

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            SnippetInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        /** @var array{_embedded: array{snippets: mixed[][]}} $list */
        $list = $listRepresentation->toArray();
        foreach ($list['_embedded']['snippets'] as &$item) {
            $item['publishedState'] = WorkflowInterface::WORKFLOW_PLACE_PUBLISHED === ($item['publishedState'] ?? null);
        }

        return new JsonResponse($this->normalizer->normalize(
            $list, // TODO maybe a listener should automatically do that for `sulu_admin` context
            'json',
            ['sulu_admin' => true, 'sulu_admin_snippet' => true, 'sulu_admin_snippet_list' => true],
        ));
    }

    public function getVersionsAction(Request $request, string $id): JsonResponse
    {
        $locale = $request->query->get('locale');

        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('snippets_versions');
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(SnippetInterface::class);
        $listBuilder->setParameter('locale', $locale);
        $listBuilder->setParameter('id', $id);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->sort($fieldDescriptors['version'], 'DESC');
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $result = $listBuilder->execute();
        $listRepresentation = new PaginatedRepresentation(
            $result,
            'snippets_versions',
            $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse(
            $this->normalizer->normalize(
                $listRepresentation->toArray(),
                'json',
            )
        );
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $dimensionAttributes = [
            'locale' => $request->query->getString('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        $snippet = $this->snippetRepository->getOneBy(
            \array_merge(
                [
                    'uuid' => $id,
                    'loadGhost' => true,
                ],
                $dimensionAttributes,
            ),
            [
                SnippetRepositoryInterface::GROUP_SELECT_SNIPPET_ADMIN => true,
            ]
        );

        // TODO the `$snippet` should just be serialized
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($snippet, $dimensionAttributes);
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent, // TODO this should just be the snippet entity see comment above
            'json',
            ['sulu_admin' => true, 'sulu_admin_snippet' => true, 'sulu_admin_snippet_content' => true],
        ));
    }

    public function postAction(Request $request): Response
    {
        $message = new CreateSnippetMessage($this->getData($request));

        /** @see Sulu\Snippet\Application\MessageHandler\CreateSnippetMessageHandler */
        /** @var SnippetInterface $snippet */
        $snippet = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        $uuid = $snippet->getUuid();

        $this->handleAction($request, $uuid);

        $response = $this->getAction($request, $uuid);

        return $response->setStatusCode(201);
    }

    public function putAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $message = new ModifySnippetMessage(['uuid' => $id], $this->getData($request));
        /** @see Sulu\Snippet\Application\MessageHandler\ModifySnippetMessageHandler */
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
        $message = new RemoveSnippetMessage(['uuid' => $id], $this->getLocale($request));
        /** @see Sulu\Snippet\Application\MessageHandler\RemoveSnippetMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new Response('', 204);
    }

    /**
     * @return array<string, mixed>
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
        return $request->query->getAlnum('locale', $request->getLocale());
    }

    private function handleAction(Request $request, string $uuid): ?SnippetInterface // @phpstan-ignore-line
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return null;
        }

        if ('copy-locale' === $action) {
            $message = new CopyLocaleSnippetMessage(
                ['uuid' => $uuid],
                (string) $request->query->get('src'),
                (string) $request->query->get('dest')
            );

            /** @see Sulu\Snippet\Application\MessageHandler\CopyLocaleSnippetMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('restore' === $action) {
            $version = \intval($request->query->get('version'));
            if (!$version) {
                throw new \InvalidArgumentException('The "version" query parameter is required for restoring a version.');
            }

            $message = new RestoreSnippetVersionMessage(
                ['uuid' => $uuid],
                $version,
                $this->getLocale($request),
                $request->query->all(),
            );

            /** @see Sulu\Snippet\Application\MessageHandler\RestoreSnippetVersionMessageHandler */
            /** @var SnippetInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } else {
            $message = new ApplyWorkflowTransitionSnippetMessage(['uuid' => $uuid], $this->getLocale($request), $action);

            /** @see Sulu\Snippet\Application\MessageHandler\ApplyWorkflowTransitionSnippetMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
    }
}
