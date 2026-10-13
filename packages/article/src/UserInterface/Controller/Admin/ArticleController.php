<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\UserInterface\Controller\Admin;

use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Application\Message\CopyArticleMessage;
use Sulu\Article\Application\Message\CopyLocaleArticleMessage;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Application\Message\ModifyArticleMessage;
use Sulu\Article\Application\Message\RemoveArticleMessage;
use Sulu\Article\Application\Message\RemoveArticleTranslationMessage;
use Sulu\Article\Application\Message\RestoreArticleVersionMessage;
use Sulu\Article\Domain\Exception\ArticleNotFoundException;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Sulu\Security\ArticleSecurityContextResolver;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @internal this class should not be instated by a project
 *           Use instead a request or response listener to
 *           extend the endpoints behaviours
 */
final class ArticleController implements SecuredControllerInterface
{
    use HandleTrait;

    /**
     * Publishing is left to the content workflow, which also lets an approved transition request through.
     */
    private const ACTION_PERMISSIONS = [
        WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH => PermissionTypes::LIVE,
        WorkflowInterface::WORKFLOW_TRANSITION_REMOVE_DRAFT => PermissionTypes::LIVE,
        'copy' => PermissionTypes::ADD,
    ];

    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        MessageBusInterface $messageBus,
        private NormalizerInterface $normalizer,
        private GroupProviderInterface $groupProvider,
        // TODO controller should not need more then Repository, MessageBus, Serializer
        private ContentManagerInterface $contentManager,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private SecurityCheckerInterface $securityChecker,
        private ArticleSecurityContextResolver $securityContextResolver,
        private bool $isSingleLocale = false,
    ) {
        $this->messageBus = $messageBus;
    }

    public function cgetAction(Request $request): Response
    {
        $groupsParam = $request->query->getString('groups', '');
        $groupIdentifiers = \array_filter(\explode(',', $groupsParam));

        $groupTemplates = [];
        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);
        foreach ($groups as $group) {
            if (\in_array($group->identifier, $groupIdentifiers, true)) {
                $groupTemplates = \array_merge($groupTemplates, $group->templates);
            }
        }

        $templateKeysParam = $request->query->getString('templateKeys', '');
        $requestedTemplateKeys = \array_filter(\explode(',', $templateKeysParam));
        $templateKeys = \array_unique(\array_merge($requestedTemplateKeys, $groupTemplates));

        // TODO this should be ArticleRepository::findFlatBy / ::countFlatBy methods
        //      but first we would need to avoid that the restHelper requires the request.
        //
        $hasFilterOrSearch = $request->query->has('search')
            || !empty($request->query->all('filter'));

        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(ArticleInterface::RESOURCE_KEY);

        if ($hasFilterOrSearch || $this->isSingleLocale) {
            foreach ($fieldDescriptors as $name => $fieldDescriptor) {
                $fieldDescriptors[$name] = $this->fieldDescriptorFactory->excludeCaseFieldDescriptor($fieldDescriptor, 'ghostDimensionContent');
            }
            unset($fieldDescriptors['ghostLocale']);
        }

        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(ArticleInterface::class);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['published']);
        $listBuilder->addSelectField($fieldDescriptors['publishedState']);
        $listBuilder->addSelectField($fieldDescriptors['templateKey']);
        $listBuilder->addSelectField($fieldDescriptors['workflowPlace']);

        if (isset($fieldDescriptors['ghostLocale'])) {
            $listBuilder->addSelectField($fieldDescriptors['ghostLocale']);
        }
        $listBuilder->setParameter('locale', $this->getLocale($request));
        $this->restrictToViewableGroups($request, $listBuilder, $fieldDescriptors['templateKey'], $groups);

        $templateFilterRequested = [] !== $groupIdentifiers || [] !== $requestedTemplateKeys;
        // the requested groups/templateKeys did not resolve to any known template key, so the
        // filter must not be dropped silently (an empty `in()` call has no effect on the query
        // and would return every article instead of none)
        $filterResolvedToNothing = $templateFilterRequested && 0 === \count($templateKeys);

        if (!$filterResolvedToNothing && 0 !== \count($templateKeys)) {
            $listBuilder->in($fieldDescriptors['templateKey'], $templateKeys);
        }

        $listRepresentation = new PaginatedRepresentation(
            $filterResolvedToNothing ? [] : $listBuilder->execute(),
            ArticleInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $filterResolvedToNothing ? 0 : $listBuilder->count(),
        );

        /** @var array{_embedded: array{articles: mixed[][]}} $list */
        $list = $listRepresentation->toArray();
        foreach ($list['_embedded']['articles'] as &$item) {
            $item['publishedState'] = WorkflowInterface::WORKFLOW_PLACE_PUBLISHED === ($item['publishedState'] ?? null);
            $templateKey = $item['templateKey'] ?? null;
            // prefixed to avoid colliding with a template property of the same name
            $item['_group'] = $this->resolveGroup($groups, \is_string($templateKey) ? $templateKey : null);
            unset($item['templateKey']);
        }

        return new JsonResponse($this->normalizer->normalize(
            $list, // TODO maybe a listener should automatically do that for `sulu_admin` context
            'json',
            ['sulu_admin' => true, 'sulu_admin_article' => true, 'sulu_admin_article_list' => true],
        ));
    }

    public function getVersionsAction(Request $request, string $id): JsonResponse
    {
        $locale = $this->getLocale($request);
        $this->checkPermission($request, PermissionTypes::VIEW, $this->getArticleContexts($id, [$locale]));

        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors('articles_versions');
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(ArticleInterface::class);
        $listBuilder->setParameter('locale', $locale);
        $listBuilder->setParameter('id', $id);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->sort($fieldDescriptors['version'], 'DESC');
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $result = $listBuilder->execute();
        $listRepresentation = new PaginatedRepresentation(
            $result,
            'articles_versions',
            $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse(
            $this->normalizer->normalize(
                $listRepresentation->toArray(),
                'json',
            ),
        );
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        return $this->createArticleResponse($request, $id, true);
    }

    private function createArticleResponse(Request $request, string $id, bool $checkViewPermission = false): Response
    {
        $dimensionAttributes = [
            'locale' => $request->query->getString('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

        try {
            $article = $this->articleRepository->getOneBy(
                \array_merge(
                    [
                        'uuid' => $id,
                        'loadGhost' => true,
                    ],
                    $dimensionAttributes,
                ),
                [
                    ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_ADMIN => true,
                ],
            );
        } catch (ArticleNotFoundException $e) {
            $exception = new EntityNotFoundException($e->getModel(), $id, $e);

            return new JsonResponse(
                $exception->toArray(),
                404
            );
        }

        // TODO the `$article` should just be serialized
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($article, $dimensionAttributes);

        $templateKey = $dimensionContent->getTemplateKey();
        $ghostLocale = $dimensionContent->getGhostLocale();
        if (null === $templateKey && null !== $ghostLocale) {
            // $article is already managed by the entity manager for the requested locale, so
            // fetching it again for $ghostLocale would return the same, already-loaded
            // dimension contents instead of querying them again. Look up just the template
            // key for the ghosted locale through the list builder instead.
            /** @var DoctrineFieldDescriptorInterface[] $ghostFieldDescriptors */
            $ghostFieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(ArticleInterface::RESOURCE_KEY);
            /** @var DoctrineListBuilder $ghostListBuilder */
            $ghostListBuilder = $this->listBuilderFactory->create(ArticleInterface::class);
            $ghostListBuilder->setIdField($ghostFieldDescriptors['id']);
            $ghostListBuilder->addSelectField($ghostFieldDescriptors['templateKey']);
            $ghostListBuilder->setIds([$id]);
            $ghostListBuilder->setParameter('locale', $ghostLocale);

            /** @var array{templateKey?: string|null}[] $ghostResult */
            $ghostResult = $ghostListBuilder->execute();
            $templateKey = $ghostResult[0]['templateKey'] ?? null;
        }

        if ($checkViewPermission) {
            $this->checkPermission(
                $request,
                PermissionTypes::VIEW,
                [$this->securityContextResolver->resolveByTemplateKey($templateKey)],
            );
        }

        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        // prefixed to avoid colliding with a template property of the same name
        $normalizedContent['_group'] = $this->resolveGroup(
            $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE),
            $templateKey,
        );

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent, // TODO this should just be the article entity see comment above
            'json',
            ['sulu_admin' => true, 'sulu_admin_article' => true, 'sulu_admin_article_content' => true],
        ));
    }

    public function postAction(Request $request): Response
    {
        $data = $this->getData($request);
        $templateKey = $this->getTemplateKey($data);
        // without a template the mapper falls back to the default type, which can belong to a group
        $contexts = [null !== $templateKey
            ? $this->securityContextResolver->resolveByTemplateKey($templateKey)
            : $this->securityContextResolver->resolveByDefaultType($this->getLocale($request))];
        $this->checkPermission($request, PermissionTypes::ADD, $contexts);
        $this->checkActionPermission($request, $contexts);

        $message = new CreateArticleMessage($data);

        /** @see \Sulu\Article\Application\MessageHandler\CreateArticleMessageHandler */
        /** @var ArticleInterface $article */
        $article = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        $uuid = $article->getUuid();

        $this->handleAction($request, $uuid);

        $response = $this->createArticleResponse($request, $uuid);

        return $response->setStatusCode(201);
    }

    public function putAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $data = $this->getData($request);

        $locale = $this->getLocale($request);
        $storedContexts = $this->securityContextResolver->resolveByArticle($id, [$locale]);
        $submittedTemplateKey = $this->getTemplateKey($data);
        if (null !== $submittedTemplateKey) {
            $targetContexts = [$this->securityContextResolver->resolveByTemplateKey($submittedTemplateKey)];
        } elseif ([] === $storedContexts && true !== ($data['shadowOn'] ?? false)) {
            // a new locale without a template gets the default type
            $targetContexts = [$this->securityContextResolver->resolveByDefaultType($locale)];
        } else {
            $targetContexts = [];
        }

        // a new locale belongs to the groups of the other locales, a new template can move the article to another group
        $articleContexts = [] === $storedContexts ? $this->securityContextResolver->resolveByArticle($id) : $storedContexts;
        $contexts = $this->withDefault(\array_values(\array_unique(\array_merge($articleContexts, $targetContexts))));

        $this->checkPermission($request, PermissionTypes::EDIT, $contexts);
        $this->checkActionPermission(
            $request,
            $contexts,
            [] !== $targetContexts ? \array_values(\array_diff($storedContexts, $targetContexts)) : [],
        );
        $this->checkSourcePermission($request, $id);

        $message = new ModifyArticleMessage(['uuid' => $id], $data);
        /** @see \Sulu\Article\Application\MessageHandler\ModifyArticleMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        $this->handleAction($request, $id);

        return $this->createArticleResponse($request, $id);
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        // the copy actions are checked on the locales they read, the request locale may have no content yet
        if (!\in_array($request->query->getString('action'), ['copy', 'copy_locale'], true)) {
            $contexts = $this->getArticleContexts($id, [$this->getLocale($request)]);
            $this->checkPermission($request, PermissionTypes::EDIT, $contexts);
            $this->checkActionPermission($request, $contexts);
        }
        $this->checkSourcePermission($request, $id);

        $result = $this->handleAction($request, $id);

        return $this->createArticleResponse($request, $result?->getUuid() ?? $id);
    }

    public function deleteAction(Request $request, string $id): Response // TODO route should be a uuid
    {
        $deleteLocale = $request->query->getBoolean('deleteLocale', false);
        $locale = $this->getLocale($request);

        $this->checkPermission($request, PermissionTypes::DELETE, $this->getArticleContexts($id, $deleteLocale ? [$locale] : []));

        if ($deleteLocale) {
            $message = new RemoveArticleTranslationMessage(['uuid' => $id], $locale);
            /** @see \Sulu\Article\Application\MessageHandler\RemoveArticleTranslationMessageHandler */
            $this->handle(new Envelope($message, [new EnableFlushStamp()]));

            return new Response('', 204);
        }

        $message = new RemoveArticleMessage(['uuid' => $id], $locale);
        /** @see \Sulu\Article\Application\MessageHandler\RemoveArticleMessageHandler */
        $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new Response('', 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function getData(Request $request): array
    {
        $data = \array_replace(
            $request->request->all(),
            [
                'locale' => $this->getLocale($request),
            ],
        );

        if ($request->query->getBoolean('force', false)) {
            unset($data['_hash']);
        }

        return $data;
    }

    public function getLocale(Request $request): string
    {
        return $request->query->getString('locale', $request->getLocale());
    }

    /**
     * @param array<string, FormGroup> $groups
     */
    private function resolveGroup(array $groups, ?string $templateKey): string
    {
        if (null !== $templateKey) {
            foreach ($groups as $group) {
                if (\in_array($templateKey, $group->templates, true)) {
                    return $group->identifier;
                }
            }
        }

        return GroupProviderInterface::DEFAULT_GROUP;
    }

    private function handleAction(Request $request, string $uuid): ?ArticleInterface // @phpstan-ignore-line
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return null;
        }

        if ('copy_locale' === $action) {
            $message = new CopyLocaleArticleMessage(
                ['uuid' => $uuid],
                (string) ($request->query->get('src') ?: $this->getLocale($request)),
                (string) $request->query->get('dest'),
            );

            /** @see \Sulu\Article\Application\MessageHandler\CopyLocaleArticleMessageHandler */
            /** @var ArticleInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('copy' === $action) {
            $message = new CopyArticleMessage(
                ['uuid' => $uuid],
                $this->getLocale($request),
            );

            /** @see \Sulu\Article\Application\MessageHandler\CopyArticleMessageHandler */
            /** @var ArticleInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('restore' === $action) {
            $version = (int) $request->query->get('version');
            if (!$version) {
                throw new \InvalidArgumentException('The "version" query parameter is required for restoring a version.');
            }

            $message = new RestoreArticleVersionMessage(
                ['uuid' => $uuid],
                $version,
                $this->getLocale($request),
                $request->query->all(),
            );

            /** @see \Sulu\Article\Application\MessageHandler\RestoreArticleVersionMessageHandler */
            /** @var ArticleInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
        $message = new ApplyWorkflowTransitionArticleMessage(['uuid' => $uuid], $this->getLocale($request), $action);

        /** @see \Sulu\Article\Application\MessageHandler\ApplyWorkflowTransitionArticleMessageHandler */
        /** @var null */
        return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
    }

    /**
     * @return string|null
     *
     * @phpstan-ignore return.type
     */
    public function getSecurityContext()
    {
        // the context depends on the group of the article, so every action checks it itself
        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getTemplateKey(array $data): ?string
    {
        $templateKey = $data['template'] ?? null;

        return \is_string($templateKey) ? $templateKey : null;
    }

    /**
     * @param list<string> $locales all locales if empty
     *
     * @return non-empty-list<string>
     */
    private function getArticleContexts(string $id, array $locales): array
    {
        return $this->withDefault($this->securityContextResolver->resolveByArticle($id, $locales));
    }

    /**
     * An article without a draft in the locale has no group, so the default one applies.
     *
     * @param list<string> $contexts
     *
     * @return non-empty-list<string>
     */
    private function withDefault(array $contexts): array
    {
        return [] === $contexts ? [$this->securityContextResolver->resolveByTemplateKey(null)] : $contexts;
    }

    /**
     * @param list<string> $contexts
     */
    private function checkPermission(Request $request, string $permission, array $contexts, ?string $locale = null): void
    {
        foreach ($contexts as $context) {
            $this->securityChecker->checkPermission(
                new SecurityCondition($context, $locale ?? $this->getLocale($request)),
                $permission,
            );
        }
    }

    /**
     * @param list<string> $contexts
     * @param list<string> $leftContexts the contexts the article leaves with a new template
     */
    private function checkActionPermission(Request $request, array $contexts, array $leftContexts = []): void
    {
        $action = $request->query->getString('action');
        $permission = self::ACTION_PERMISSIONS[$action] ?? null;

        if (null !== $permission) {
            $this->checkPermission($request, $permission, $contexts);
        }

        // publishing replaces the live content of the group the article leaves
        if (WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH === $action) {
            $this->checkPermission($request, PermissionTypes::LIVE, $leftContexts);
        }
    }

    /**
     * The copy and restore actions take content from other locales or versions than the request locale, and with it
     * their groups. `putAction` and `postTriggerAction` both run these actions, so both check them here.
     */
    private function checkSourcePermission(Request $request, string $id): void
    {
        $locale = $this->getLocale($request);

        switch ($request->query->getString('action')) {
            case 'copy':
                $contexts = $this->getArticleContexts($id, []); // copies every locale
                $this->checkPermission($request, PermissionTypes::EDIT, $contexts);
                $this->checkActionPermission($request, $contexts);

                break;
            case 'copy_locale':
                $contexts = $this->getArticleContexts($id, [(string) ($request->query->get('src') ?: $locale), $request->query->getString('dest')]);
                $this->checkPermission($request, PermissionTypes::EDIT, $contexts);
                // the copy creates content in the destination locale, which has its own permissions
                $this->checkPermission($request, PermissionTypes::EDIT, $contexts, $request->query->getString('dest') ?: null);

                break;
            case 'restore':
                $this->checkPermission($request, PermissionTypes::EDIT, $this->securityContextResolver->resolveByVersion(
                    $id,
                    $locale,
                    (int) $request->query->get('version'),
                    $request->query->getString('stage') ?: DimensionContentInterface::STAGE_DRAFT,
                ));

                break;
        }
    }

    /**
     * Articles of a template in no group are only listed to a user who may view everything.
     *
     * @param array<string, FormGroup> $groups
     */
    private function restrictToViewableGroups(
        Request $request,
        DoctrineListBuilder $listBuilder,
        DoctrineFieldDescriptorInterface $templateKeyFieldDescriptor,
        array $groups,
    ): void {
        $defaultCondition = new SecurityCondition(
            $this->securityContextResolver->resolveByTemplateKey(null),
            $this->getLocale($request),
        );
        $isRestricted = !$this->securityChecker->hasPermission($defaultCondition, PermissionTypes::VIEW);

        $viewableTemplateKeys = [];
        foreach ($groups as $group) {
            $condition = new SecurityCondition(
                $this->securityContextResolver->resolveByGroup($group, $groups),
                $this->getLocale($request),
            );

            if ($this->securityChecker->hasPermission($condition, PermissionTypes::VIEW)) {
                $viewableTemplateKeys = \array_merge($viewableTemplateKeys, $group->templates);
            } else {
                $isRestricted = true;
            }
        }

        if (!$isRestricted) {
            return;
        }

        if ([] === $viewableTemplateKeys) {
            throw new AccessDeniedException(\sprintf(
                'Permission "%s" for context "%s" not granted',
                PermissionTypes::VIEW,
                $defaultCondition->getSecurityContext(),
            ));
        }

        $listBuilder->in($templateKeyFieldDescriptor, $viewableTemplateKeys);
    }
}
