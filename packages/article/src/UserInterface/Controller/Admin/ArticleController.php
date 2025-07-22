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
use Sulu\Article\Application\Message\CopyLocaleArticleMessage;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Application\Message\ModifyArticleMessage;
use Sulu\Article\Application\Message\RemoveArticleMessage;
use Sulu\Article\Application\Message\RestoreArticleVersionMessage;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
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
final class ArticleController
{
    use HandleTrait;

    /**
     * @var ArticleRepositoryInterface
     */
    private $articleRepository;

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
        ArticleRepositoryInterface $articleRepository,
        MessageBusInterface $messageBus,
        NormalizerInterface $normalizer,
        ContentManagerInterface $contentManager,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        DoctrineListBuilderFactoryInterface $listBuilderFactory,
        RestHelperInterface $restHelper
    ) {
        $this->articleRepository = $articleRepository;
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
        // TODO this should be ArticleRepository::findFlatBy / ::countFlatBy methods
        //      but first we would need to avoid that the restHelper requires the request.
        //
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(ArticleInterface::RESOURCE_KEY);
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(ArticleInterface::class);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['ghostLocale']);
        $listBuilder->addSelectField($fieldDescriptors['published']);
        $listBuilder->addSelectField($fieldDescriptors['publishedState']);
        $listBuilder->setParameter('locale', $request->query->get('locale'));
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            ArticleInterface::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        /** @var array{_embedded: array{articles: mixed[][]}} $list */
        $list = $listRepresentation->toArray();
        foreach ($list['_embedded']['articles'] as &$item) {
            $item['publishedState'] = WorkflowInterface::WORKFLOW_PLACE_PUBLISHED === ($item['publishedState'] ?? null);
        }

        return new JsonResponse($this->normalizer->normalize(
            $list, // TODO maybe a listener should automatically do that for `sulu_admin` context
            'json',
            ['sulu_admin' => true, 'sulu_admin_article' => true, 'sulu_admin_article_list' => true],
        ));
    }

    public function getVersionsAction(Request $request, string $id): JsonResponse
    {
        $locale = $request->query->get('locale');

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
            )
        );
    }

    public function getAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $dimensionAttributes = [
            'locale' => $request->query->getString('locale', $request->getLocale()),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ];

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
            ]
        );

        // TODO the `$article` should just be serialized
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($article, $dimensionAttributes);
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent, // TODO this should just be the article entity see comment above
            'json',
            ['sulu_admin' => true, 'sulu_admin_article' => true, 'sulu_admin_article_content' => true],
        ));
    }

    public function postAction(Request $request): Response
    {
        $message = new CreateArticleMessage($this->getData($request));

        /** @see Sulu\Article\Application\MessageHandler\CreateArticleMessageHandler */
        /** @var ArticleInterface $article */
        $article = $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        $uuid = $article->getUuid();

        $this->handleAction($request, $uuid);

        $response = $this->getAction($request, $uuid);

        return $response->setStatusCode(201);
    }

    public function putAction(Request $request, string $id): Response // TODO route should be a uuid?
    {
        $message = new ModifyArticleMessage(['uuid' => $id], $this->getData($request));
        /** @see Sulu\Article\Application\MessageHandler\ModifyArticleMessageHandler */
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
        $message = new RemoveArticleMessage(['uuid' => $id]);
        /** @see Sulu\Article\Application\MessageHandler\RemoveArticleMessageHandler */
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
        return $request->query->getString('locale', $request->getLocale());
    }

    private function handleAction(Request $request, string $uuid): ?ArticleInterface // @phpstan-ignore-line
    {
        $action = $request->query->get('action');

        if (!$action || 'draft' === $action) {
            return null;
        }

        if ('copy-locale' === $action) {
            $message = new CopyLocaleArticleMessage(
                ['uuid' => $uuid],
                (string) $request->query->get('src'),
                (string) $request->query->get('dest')
            );

            /** @see Sulu\Article\Application\MessageHandler\CopyLocaleArticleMessageHandler */
            /** @var ArticleInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } elseif ('restore' === $action) {
            $version = \intval($request->query->get('version'));
            if (!$version) {
                throw new \InvalidArgumentException('The "version" query parameter is required for restoring a version.');
            }

            $message = new RestoreArticleVersionMessage(
                ['uuid' => $uuid],
                $version,
                $request->query->all(),
            );

            /** @see Sulu\Article\Application\MessageHandler\RestoreArticleVersionMessageHandler */
            /** @var ArticleInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        } else {
            $message = new ApplyWorkflowTransitionArticleMessage(['uuid' => $uuid], $this->getLocale($request), $action);

            /** @see Sulu\Article\Application\MessageHandler\ApplyWorkflowTransitionArticleMessageHandler */
            /** @var null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }
    }
}
