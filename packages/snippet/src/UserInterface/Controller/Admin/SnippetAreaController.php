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

use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Snippet\Application\Message\ModifySnippetAreaMessage;
use Sulu\Snippet\Application\Message\RemoveSnippetAreaMessage;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
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
final class SnippetAreaController
{
    use HandleTrait;

    /**
     * @param SnippetAreaConfig $snippetAreas
     */
    public function __construct(
        MessageBusInterface $messageBus,
        private NormalizerInterface $normalizer,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private array $snippetAreas,
    ) {
        $this->messageBus = $messageBus;
    }

    public function cgetAction(Request $request): Response
    {
        /** @var DoctrineFieldDescriptorInterface[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(SnippetAreaInterface::RESOURCE_KEY);

        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create(SnippetAreaInterface::class);
        $listBuilder->setIdField($fieldDescriptors['id']);
        $listBuilder->addSelectField($fieldDescriptors['snippetUuid']);
        $listBuilder->addSelectField($fieldDescriptors['snippetTitle']);
        $listBuilder->setParameter('locale', $this->getLocale($request));

        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $webspaceKey = $request->query->getString('webspaceKey');
        if ($webspaceKey) {
            $listBuilder->where($fieldDescriptors['webspaceKey'], $webspaceKey);
        }

        $result = $listBuilder->execute();

        $snippetAreas = [];
        foreach ($result as $row) {
            if (\is_array($row) && isset($row['areaKey'])) {
                /** @var string $areaKey */
                $areaKey = $row['areaKey'];
                $snippetAreas[$areaKey] = $row;
            }
        }

        // Add the empty snippet areas as placeholders
        foreach ($this->snippetAreas as $key => $snippetArea) {
            $existingData = $snippetAreas[$key] ?? [];
            $snippetAreas[$key] =
                \array_merge(
                    [
                        'key' => $key,
                        'snippetTitle' => null,
                        'snippetUuid' => null,
                        'templateKey' => $snippetArea['template'],
                        'title' => $snippetArea['title'][$this->getLocale($request)],
                    ],
                    $existingData
                );
        }

        $listRepresentation = new CollectionRepresentation(
            \array_values($snippetAreas),
            SnippetAreaInterface::RESOURCE_KEY,
        );

        return new JsonResponse($this->normalizer->normalize(
            $listRepresentation->toArray(),
            'json',
            [
                'locale' => $this->getLocale($request),
                'sulu_admin' => true,
                'sulu_admin_snippet' => true,
                'sulu_admin_snippet_list' => true,
            ],
        ));
    }

    public function putAction(Request $request, string $key): JsonResponse
    {
        $snippetUuid = $request->request->get('snippetUuid');
        if (!\is_string($snippetUuid)) {
            throw new \InvalidArgumentException('snippetUuid must be a string.');
        }

        $data = [
            'webspaceKey' => $request->query->getString('webspaceKey'),
            'snippetIdentifier' => ['uuid' => $snippetUuid],
            'key' => $key,
        ];
        $message = new ModifySnippetAreaMessage($data);

        /** @see \Sulu\Snippet\Application\MessageHandler\ModifySnippetMessageHandler */
        $updatedSnippetArea = $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new JsonResponse($this->normalizer->normalize(
            $updatedSnippetArea,
            'json',
            [
                'locale' => $this->getLocale($request),
                'sulu_admin' => true,
                'sulu_admin_snippet' => true,
            ],
        ));
    }

    public function deleteAction(Request $request, string $key): Response
    {
        $data = [
            'webspaceKey' => $request->query->getString('webspaceKey'),
            'areaKey' => $key,
        ];
        $message = new RemoveSnippetAreaMessage($data);

        /** @see \Sulu\Snippet\Application\MessageHandler\RemoveSnippetAreaMessageHandler */
        $deletedSnippetArea = $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new JsonResponse($this->normalizer->normalize(
            $deletedSnippetArea,
            'json',
            [
                'locale' => $this->getLocale($request),
                'sulu_admin' => true,
                'sulu_admin_snippet' => true,
            ],
        ));
    }

    private function getLocale(Request $request): string
    {
        $locale = $request->query->get('locale');
        if (\is_string($locale)) {
            return $locale;
        }

        return $request->getLocale();
    }
}
