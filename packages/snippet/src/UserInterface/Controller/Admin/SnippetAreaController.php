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
use Sulu\Snippet\Domain\Model\SnippetArea;
use Sulu\Snippet\Domain\Model\SnippetAreaInterface;
use Sulu\Snippet\Domain\Repository\SnippetAreaRepositoryInterface;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

/**
 * @internal this class should not be instated by a project
 *           Use instead a request or response listener to
 *           extend the endpoints behaviours
 *
 * @phpstan-import-type Entry from SnippetAreaCompilerPass
 */
final class SnippetAreaController
{
    use HandleTrait;

    /**
     * @param array<Entry> $snippetArea
     */
    public function __construct(
        MessageBusInterface $messageBus,
        private NormalizerInterface $normalizer,
        //private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        //private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        //private RestHelperInterface $restHelper,
        private SnippetAreaRepositoryInterface $snippetAreaRepository,
        private array $snippetArea,
    ) {
        // Setting the message bus of the HandleTrait
        $this->messageBus = $messageBus;
    }

    public function cgetAction(Request $request): Response
    {
        /* @var DoctrineFieldDescriptorInterface[]|null $fieldDescriptors */
        //$fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(SnippetAreaInterface::RESOURCE_KEY);
        //Assert::notNull($fieldDescriptors, 'Could not find field descriptors for resource key: ' . SnippetAreaInterface::RESOURCE_KEY);

        /* @var DoctrineListBuilder $listBuilder */
        //$listBuilder = $this->listBuilderFactory->create(SnippetAreaInterface::class);
        //$listBuilder->setIdField($fieldDescriptors['id']); // We need to set this because it's the uuid doctrine column
        //$listBuilder->setParameter('locale', $request->query->get('locale'));
        //$listBuilder->setParameter('webspace', $request->query->get('webspace'));

        //$this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);
        $webspaceKey = $request->query->getString('webspace');

        $snippetAreas = $this->snippetAreaRepository->findByWebspace($webspaceKey);

        // Add the empty snippet areas as placeholders
        foreach ($this->snippetArea as $snippetArea) {
            $key = $snippetArea['key'];
            if (!\array_key_exists($key, $snippetAreas)) {
                $snippetAreas[$key] = new SnippetArea(
                    areaKey: $snippetArea['key'],
                    webspaceKey: $webspaceKey,
                );
            }
        }

        $listRepresentation = new CollectionRepresentation(
            \array_values($snippetAreas),
            SnippetAreaInterface::RESOURCE_KEY,
        );

        return new JsonResponse($this->normalizer->normalize(
            $listRepresentation->toArray(),
            'json',
            [
                'locale' => $request->getLocale(),
                'sulu_admin' => true,
                'sulu_admin_snippet' => true,
                'sulu_admin_snippet_list' => true,
            ],
        ));
    }

    public function putAction(Request $request): JsonResponse
    {
        $message = new ModifySnippetAreaMessage([
            ...$request->attributes->all('_route_params'),
            ...$request->request->all(),
        ]);

        /** @see \Sulu\Snippet\Application\MessageHandler\ModifySnippetMessageHandler */
        $updatedSnippetArea = $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new JsonResponse($this->normalizer->normalize(
            $updatedSnippetArea,
            'json',
            [
                'locale' => $request->getLocale(),
                'sulu_admin' => true,
                'sulu_admin_snippet' => true,
            ],
        ));
    }

    public function deleteAction(Request $request): Response
    {
        $message = new RemoveSnippetAreaMessage($request->attributes->all());

        /** @see \Sulu\Snippet\Application\MessageHandler\RemoveSnippetAreaMessageHandler */
        $deletedSnippetArea = $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return new JsonResponse($this->normalizer->normalize(
            $deletedSnippetArea,
            'json',
            [
                'locale' => $request->getLocale(),
                'sulu_admin' => true,
                'sulu_admin_snippet' => true,
            ],
        ));
    }
}
