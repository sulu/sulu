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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CopyLocalePageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\Message\OrderPageMessage;
use Sulu\Page\Application\Message\RemovePageMessage;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
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

    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private MessageBusInterface $messageBus, // @phpstan-ignore property.onlyWritten
        private NormalizerInterface $normalizer,
        private ContentManagerInterface $contentManager,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private EntityManagerInterface $entityManager,
    ) {
        // TODO controller should not need more then Repository, MessageBus, Serializer
    }

    public function cgetAction(Request $request): Response
    {
        $locale = $request->query->get('locale');
        $parentId = $request->query->get('parentId');
        $webspaceKey = $request->query->get('webspace');
        $excludeGhosts = $request->query->getBoolean('exclude-ghosts', false);
        $excludeShadows = $request->query->getBoolean('exclude-shadows', false);
        $expandedIds = \array_filter(\explode(',', (string) $request->query->get('expandedIds')));

        $filters = [];

        if ($webspaceKey) {
            $filters['webspaceKey'] = $webspaceKey;
        }

        if ($excludeGhosts) {
            $filters['ghostLocale'] = null;
        }

        if ($excludeShadows) {
            $filters['shadowLocale'] = null;
        }

        $includedFields = ['locale', 'ghostLocale', 'shadowLocale', 'webspaceKey', 'template', 'publishedState'];

        // TODO this should be handled by PageRepository, currently copied from
        //      https://github.com/handcraftedinthealps/SuluResourceBundle
        //      see ListRepresentation/DoctrineNestedListRepresentationFactory.php
        $representation = $this->createDoctrineListRepresentation(
            resourceKey: PageInterface::RESOURCE_KEY,
            filters: $filters,
            parameters: ['locale' => $locale],
            parentId: $parentId,
            expandedIds: $expandedIds,
            includedFields: $includedFields,
            listKey: 'pages_next',
        );

        return new JsonResponse($this->normalizer->normalize(
            $representation->toArray(), // TODO maybe a listener should automatically do that for `sulu_admin` context
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
            ],
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
        $webspaceKey = $request->query->getString('webspace');
        $parentId = $request->query->getString('parentId');
        $message = new CreatePageMessage($webspaceKey, $parentId, $this->getData($request));

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
            ],
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
                (string) $request->query->get('dest'),
            );

            /** @see Sulu\Page\Application\MessageHandler\CopyLocalePageMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('order' === $action) {
            $position = $request->request->getInt('position');
            $message = new OrderPageMessage(
                ['uuid' => $uuid],
                $position,
            );

            /** @see Sulu\Page\Application\MessageHandler\OrderPageMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
        $message = new ApplyWorkflowTransitionPageMessage(['uuid' => $uuid], $this->getLocale($request), $action);

        /** @see Sulu\Page\Application\MessageHandler\ApplyWorkflowTransitionPageMessageHandler */
        /** @var null */
        return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
    }

    /**
     * @param array<string, bool|float|int|string|null> $filters
     * @param array<string, mixed> $parameters
     * @param string[] $expandedIds
     * @param string[] $includedFields
     * @param string[] $groupByFields
     */
    private function createDoctrineListRepresentation(
        string $resourceKey,
        array $filters = [],
        array $parameters = [],
        ?string $parentId = null,
        array $expandedIds = [],
        array $includedFields = [],
        array $groupByFields = [],
        ?string $listKey = null,
    ): CollectionRepresentation {
        $listKey = $listKey ?? $resourceKey;

        /** @var DoctrineFieldDescriptor[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors($listKey);

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($fieldDescriptors['id']->getEntityName());
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        foreach ($parameters as $key => $value) {
            $listBuilder->setParameter($key, $value);
        }

        foreach ($filters as $key => $value) {
            $listBuilder->where($fieldDescriptors[$key], $value); // @phpstan-ignore argument.type
        }

        foreach ($includedFields as $field) {
            $listBuilder->addSelectField($fieldDescriptors[$field]);
        }

        foreach ($groupByFields as $field) {
            $listBuilder->addGroupBy($fieldDescriptors[$field]);
        }

        // disable pagination to simplify tree handling and select tree related properties that are used below
        $listBuilder->limit(\PHP_INT_MAX);
        $listBuilder->addSelectField($fieldDescriptors['lft']);
        $listBuilder->addSelectField($fieldDescriptors['rgt']);
        $listBuilder->addSelectField($fieldDescriptors['parentId']);
        $listBuilder->sort($fieldDescriptors['lft'], 'asc');

        // collect entities of which the children should be included in the response
        $idsToExpand = \array_merge(
            [$parentId],
            $this->findIdsOnPathsBetween($fieldDescriptors['id']->getEntityName(), $parentId, $expandedIds),
            $expandedIds,
        );

        // generate expressions to select only entities that are children of the collected expand-entities
        $expandExpressions = [];
        foreach ($idsToExpand as $idToExpand) {
            $expandExpressions[] = $listBuilder->createWhereExpression(
                $fieldDescriptors['parentId'],
                $idToExpand,
                ListBuilderInterface::WHERE_COMPARATOR_EQUAL,
            );
        }

        if (1 === \count($expandExpressions)) {
            $listBuilder->addExpression($expandExpressions[0]);
        } else {
            $orExpression = $listBuilder->createOrExpression($expandExpressions);
            $listBuilder->addExpression($orExpression);
        }

        /** @var mixed[][] $rows */
        $rows = $listBuilder->execute();

        return new CollectionRepresentation(
            $this->generateNestedRows($parentId, $resourceKey, $rows),
            $resourceKey,
        );
    }

    /**
     * @param string[] $endIds
     *
     * @return mixed[]
     */
    private function findIdsOnPathsBetween(string $entityClass, int|string|null $startId, array $endIds): array
    {
        // there are no paths and therefore no ids if we dont have any end-ids
        if (0 === \count($endIds)) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from($entityClass, 'entity')
            ->select('entity.uuid');

        // if this start-id is not set we want to include all paths from the root to our end-ids
        if ($startId) {
            $queryBuilder->from($entityClass, 'startEntity')
                ->andWhere('startEntity.uuid = :startIds')
                ->andWhere('entity.lft > startEntity.lft')
                ->andWhere('entity.rgt < startEntity.rgt')
                ->setParameter('startIds', $startId);
        }

        $queryBuilder->from($entityClass, 'endEntity')
            ->andWhere('endEntity.uuid IN (:endIds)')
            ->andWhere('entity.lft < endEntity.lft')
            ->andWhere('entity.rgt > endEntity.rgt')
            ->setParameter('endIds', $endIds);

        return \array_map('current', $queryBuilder->getQuery()->getScalarResult()); // @phpstan-ignore argument.type
    }

    /**
     * @param mixed[][] $flatRows
     *
     * @return mixed[]
     */
    private function generateNestedRows(?string $parentId, string $resourceKey, array $flatRows): array
    {
        // add hasChildren property that is expected by the sulu frontend
        foreach ($flatRows as &$row) {
            /** @var int $lft */
            $lft = $row['lft'];
            $row['hasChildren'] = ($lft + 1) !== $row['rgt'];
        }

        // group rows by the id of their parent
        $rowsByParentId = [];
        foreach ($flatRows as &$row) {
            /** @var string $rowParentId */
            $rowParentId = $row['parentId'];
            if (!\array_key_exists($rowParentId, $rowsByParentId)) {
                $rowsByParentId[$rowParentId] = [];
            }
            $rowsByParentId[$rowParentId][] = &$row;
        }

        // embed children rows int their parent rows
        foreach ($flatRows as &$row) {
            // TODO this should be handled by the listbuilder
            $row['publishedState'] = WorkflowInterface::WORKFLOW_PLACE_PUBLISHED === $row['publishedState'];

            /** @var string $rowId */
            $rowId = $row['id'];
            if (\array_key_exists($rowId, $rowsByParentId)) {
                $row['_embedded'] = [
                    $resourceKey => $rowsByParentId[$rowId],
                ];
            }
        }

        // remove tree related properties from the response
        foreach ($flatRows as &$row) {
            unset($row['rgt']);
            unset($row['lft']);
            unset($row['parentId']);
        }

        return $rowsByParentId[$parentId] ?? [];
    }
}
