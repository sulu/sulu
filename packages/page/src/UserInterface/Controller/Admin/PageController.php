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

use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredObjectControllerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CopyLocalePageMessage;
use Sulu\Page\Application\Message\CopyPageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\Message\MovePageMessage;
use Sulu\Page\Application\Message\OrderPageMessage;
use Sulu\Page\Application\Message\RemovePageMessage;
use Sulu\Page\Application\Message\RemovePageTranslationMessage;
use Sulu\Page\Application\Message\RestorePageVersionMessage;
use Sulu\Page\Domain\Exception\PageNotFoundException;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal this class should not be instated by a project
 *           Use instead a request or response listener to
 *           extend the endpoints behaviours
 */
final class PageController implements SecuredControllerInterface, SecuredObjectControllerInterface
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
        private AccessControlManagerInterface $accessControlManager,
        private TokenStorageInterface $tokenStorage,
        private WebspaceManagerInterface $webspaceManager,
        private SecurityCheckerInterface $securityChecker,
    ) {
        // TODO controller should not need more then Repository, MessageBus, Serializer
    }

    public function cgetAction(Request $request): Response
    {
        $locale = $request->query->get('locale');
        $parentId = $request->query->get('parentId');
        $webspaceKey = $request->query->get('webspace');
        $excludeGhosts = $request->query->getBoolean('exclude-ghosts', false);
        $hasFilterOrSearch = $request->query->has('search')
            || !empty($request->query->all('filter'));
        $excludeShadows = $request->query->getBoolean('exclude-shadows', false);
        $expandedIds = \array_filter(\explode(',', (string) $request->query->get('expandedIds')));
        $ids = $request->query->get('ids');

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

        $includedFields = ['locale', 'ghostLocale', 'shadowLocale', 'webspaceKey', 'template', 'publishedState', 'linkProvider'];

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
            listKey: 'pages',
            filterByParentId: empty($ids),
            excludeGhosts: $hasFilterOrSearch,
        );

        return new JsonResponse($this->normalizer->normalize(
            $representation->toArray(), // TODO maybe a listener should automatically do that for `sulu_admin` context
            'json',
            ['sulu_admin' => true, 'sulu_admin_page' => true, 'sulu_admin_page_list' => true],
        ));
    }

    public function getVersionsAction(Request $request, string $id): JsonResponse
    {
        $locale = $request->query->get('locale');

        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('pages_versions');
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(PageInterface::class);
        $listBuilder->setParameter('locale', $locale);
        $listBuilder->setParameter('id', $id);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->sort($fieldDescriptors['version'], 'DESC');
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $result = $listBuilder->execute();
        $listRepresentation = new PaginatedRepresentation(
            $result,
            'pages_versions',
            $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse(
            $this->normalizer->normalize(
                $listRepresentation->toArray(),
                'json',
                ['sulu_admin' => true, 'sulu_admin_page' => true, 'sulu_admin_page_list' => true],
            ),
        );
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $dimensionAttributes = [
            'locale' => $request->query->getString('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        try {
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
        } catch (PageNotFoundException $e) {
            $exception = new EntityNotFoundException($e->getModel(), $id, $e);

            return new JsonResponse(
                $exception->toArray(),
                404,
            );
        }

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

        /** @see \Sulu\Page\Application\MessageHandler\CreatePageMessageHandler */
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
        /** @see \Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        $this->handleAction($request, $id);

        return $this->getAction($request, $id);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        $result = $this->handleAction($request, $id);

        return $this->getAction($request, $result?->getUuid() ?? $id);
    }

    public function deleteAction(Request $request, string $id): Response // TODO route should be a uuid
    {
        $deleteLocale = $request->query->getBoolean('deleteLocale', false);
        $forceRemoveChildren = $request->query->getBoolean('force', false);
        $locale = $this->getLocale($request);

        if ($deleteLocale) {
            $message = new RemovePageTranslationMessage(['uuid' => $id], $locale);
            /** @see \Sulu\Page\Application\MessageHandler\RemovePageTranslationMessageHandler */
            $this->handle(new Envelope($message, [new EnableFlushStamp()]));

            return new Response('', 204);
        }

        $message = new RemovePageMessage(['uuid' => $id], $locale, $forceRemoveChildren);
        /** @see \Sulu\Page\Application\MessageHandler\RemovePageMessageHandler */
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

    public function getLocale(Request $request): string
    {
        return $request->query->getString('locale', $request->getLocale());
    }

    private function handleAction(Request $request, string $uuid): ?PageInterface // @phpstan-ignore-line
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return null;
        }

        if ('copy_locale' === $action) {
            $srcLocale = (string) ($request->query->get('src') ?: $request->query->get('locale'));
            $destLocales = \array_filter(\array_map('trim', \explode(',', (string) $request->query->get('dest'))));

            $result = null;
            foreach ($destLocales as $destLocale) {
                $message = new CopyLocalePageMessage(
                    ['uuid' => $uuid],
                    $srcLocale,
                    $destLocale,
                );

                /** @see \Sulu\Page\Application\MessageHandler\CopyLocalePageMessageHandler */
                /** @var PageInterface|null */
                $result = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
            }

            return $result;
        } elseif ('move' === $action) {
            $destinationUuid = $request->query->getString('destination');
            $message = new MovePageMessage(['uuid' => $uuid], ['uuid' => $destinationUuid], $this->getLocale($request));

            /** @see \Sulu\Page\Application\MessageHandler\MovePageMessageHandler */
            /** @var PageInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('copy' === $action) {
            $destinationUuid = $request->query->getString('destination');
            $message = new CopyPageMessage(['uuid' => $uuid], ['uuid' => $destinationUuid], $this->getLocale($request));

            /** @see \Sulu\Page\Application\MessageHandler\CopyPageMessageHandler */
            /** @var PageInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('order' === $action) {
            $position = $request->request->getInt('position');
            $message = new OrderPageMessage(
                ['uuid' => $uuid],
                $position,
                $this->getLocale($request),
            );

            /** @see \Sulu\Page\Application\MessageHandler\OrderPageMessageHandler */
            /** @var PageInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('restore' === $action) {
            $version = $request->query->getInt('version');
            if (!$version) {
                throw new \InvalidArgumentException('The "version" query parameter is required for restoring a version.');
            }

            $message = new RestorePageVersionMessage(
                ['uuid' => $uuid],
                $version,
                $this->getLocale($request),
                $request->query->all(),
            );

            /** @see \Sulu\Page\Application\MessageHandler\RestorePageVersionMessageHandler */
            /** @var PageInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }

        $message = new ApplyWorkflowTransitionPageMessage(['uuid' => $uuid], $this->getLocale($request), $action);

        /** @see \Sulu\Page\Application\MessageHandler\ApplyWorkflowTransitionPageMessageHandler */
        /** @var PageInterface|null */
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
        bool $filterByParentId = true,
        bool $excludeGhosts = false,
    ): CollectionRepresentation {
        $listKey = $listKey ?? $resourceKey;

        /** @var DoctrineFieldDescriptor[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors($listKey, $excludeGhosts);

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($fieldDescriptors['id']->getEntityName());
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        foreach ($parameters as $key => $value) {
            $listBuilder->setParameter($key, $value);
        }

        /** @var string|null $webspaceKey */
        $webspaceKey = $filters['webspaceKey'] ?? null;
        unset($filters['webspaceKey']);

        if ($webspaceKey) {
            $webspaces = [$this->getWebspaceKey($webspaceKey)];
        } else {
            $webspaces = $this->getWebspaceKeys();
        }

        $listBuilder->in($fieldDescriptors['webspaceKey'], $webspaces);

        foreach ($filters as $key => $value) {
            if (isset($fieldDescriptors[$key])) {
                $listBuilder->where($fieldDescriptors[$key], $value); // @phpstan-ignore argument.type
            }
        }

        foreach ($includedFields as $field) {
            if (isset($fieldDescriptors[$field])) {
                $listBuilder->addSelectField($fieldDescriptors[$field]);
            }
        }

        foreach ($groupByFields as $field) {
            $listBuilder->addGroupBy($fieldDescriptors[$field]);
        }

        // disable pagination to simplify tree handling and select tree related properties that are used below
        $listBuilder->limit(\PHP_INT_MAX);
        $listBuilder->addSelectField($fieldDescriptors['lft']);
        $listBuilder->addSelectField($fieldDescriptors['rgt']);
        $listBuilder->addSelectField($fieldDescriptors['parentId']);
        $listBuilder->addSelectField($fieldDescriptors['version']);
        $listBuilder->sort($fieldDescriptors['lft'], 'asc');

        // If no expandedIds provided, return flat list
        if (empty($expandedIds)) {
            if ($filterByParentId) {
                $listBuilder->where($fieldDescriptors['parentId'], $parentId);
            }

            /** @var mixed[][] $rows */
            $rows = $listBuilder->execute();
            $enhancedRows = $this->enhanceRows($rows);

            // Remove tree properties for flat list
            foreach ($enhancedRows as &$row) {
                unset($row['rgt']);
                unset($row['lft']);
                unset($row['parentId']);
            }

            return new CollectionRepresentation(
                $enhancedRows,
                $resourceKey,
            );
        }

        // Generate nested structure when expandedIds are provided
        // collect entities of which the children should be included in the response
        $pathIds = $parentId
            ? \iterator_to_array($this->pageRepository->findIdentifiersBy([
                'ancestorsOfIds' => $expandedIds,
                'descendantOfId' => $parentId,
            ]))
            : \iterator_to_array($this->pageRepository->findIdentifiersBy([
                'ancestorsOfIds' => $expandedIds,
            ]));

        $idsToExpand = \array_merge(
            [$parentId],
            $pathIds,
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
        $enhancedRows = $this->enhanceRows($rows);

        return new CollectionRepresentation(
            $this->generateNestedRows($parentId, $resourceKey, $enhancedRows),
            $resourceKey,
        );
    }

    /**
     * @param mixed[][] $rows
     *
     * @return mixed[][]
     */
    private function enhanceRows(array $rows): array
    {
        foreach ($rows as &$row) {
            /** @var int $lft */
            $lft = $row['lft'];
            $row['hasChildren'] = ($lft + 1) !== $row['rgt'];
        }

        foreach ($rows as &$row) {
            // TODO this should be handled by the listbuilder
            $row['publishedState'] = WorkflowInterface::WORKFLOW_PLACE_PUBLISHED === $row['publishedState'];

            /** @var string $rowId */
            $rowId = $row['id'];
            /** @var string $webspaceKey */
            $webspaceKey = $row['webspaceKey'];

            $allPermissions = $this->accessControlManager->getPermissions(
                Page::class,
                $rowId,
            );
            $row['_hasPermissions'] = !empty($allPermissions);

            if (!empty($allPermissions)) {
                $token = $this->tokenStorage->getToken();
                $user = $token?->getUser();

                if ($user instanceof UserInterface) {
                    $permissions = $this->accessControlManager->getUserPermissionByArray(
                        null,
                        \sprintf('sulu.webspaces.%s', $webspaceKey),
                        $allPermissions,
                        $user,
                    );
                    $row['_permissions'] = $permissions;
                } else {
                    $row['_permissions'] = [];
                }
            }
        }

        return $rows;
    }

    /**
     * @param mixed[][] $flatRows
     *
     * @return mixed[]
     */
    private function generateNestedRows(?string $parentId, string $resourceKey, array $flatRows): array
    {
        $rowsByParentId = [];
        foreach ($flatRows as &$row) {
            /** @var string $rowParentId */
            $rowParentId = $row['parentId'];
            if (!\array_key_exists($rowParentId, $rowsByParentId)) {
                $rowsByParentId[$rowParentId] = [];
            }
            $rowsByParentId[$rowParentId][] = &$row;
        }

        foreach ($flatRows as &$row) {
            /** @var string $rowId */
            $rowId = $row['id'];

            if (\array_key_exists($rowId, $rowsByParentId)) {
                $row['_embedded'] = [
                    $resourceKey => $rowsByParentId[$rowId],
                ];
            }
        }

        foreach ($flatRows as &$row) {
            unset($row['rgt']);
            unset($row['lft']);
            unset($row['parentId']);
        }

        return $rowsByParentId[$parentId] ?? [];
    }

    /**
     * @return string|null
     *
     * @phpstan-ignore return.type
     */
    public function getSecurityContext()
    {
        // Pages have webspace-specific security contexts, but we can't determine
        // the webspace here without the Request. We return null and rely on
        // object-level permissions via SecuredObjectControllerInterface.
        // The SuluSecurityListener will load the Page entity and get its
        // security context dynamically.
        return null;
    }

    public function getSecuredClass()
    {
        return Page::class;
    }

    public function getSecuredObjectId(Request $request): string
    {
        // For detail actions, use id parameter
        // For list action, no specific object (will check webspace-level permissions)
        $id = $request->get('id');

        return \is_string($id) ? $id : '';
    }

    /**
     * @return string[]
     */
    private function getWebspaceKeys(): array
    {
        $webspaceKeys = [];

        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            if ($this->securityChecker->hasPermission(
                PageAdmin::getPageSecurityContext($webspace->getKey()),
                PermissionTypes::VIEW,
            )) {
                $webspaceKeys[] = $webspace->getKey();
            }
        }

        return $webspaceKeys;
    }

    private function getWebspaceKey(string $webspaceKey): ?string
    {
        /** @var Webspace $webspace */
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        if ($this->securityChecker->hasPermission(
            PageAdmin::getPageSecurityContext($webspace->getKey()),
            PermissionTypes::VIEW,
        )) {
            return $webspace->getKey();
        }

        return null;
    }
}
