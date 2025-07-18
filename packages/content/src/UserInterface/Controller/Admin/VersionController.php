<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\UserInterface\Controller\Admin;

use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\Message\RestoreContentVersionMessage;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class VersionController
{
    use HandleTrait;

    public function __construct(
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
        private NormalizerInterface $normalizer,
        private MessageBusInterface $messageBus, // @phpstan-ignore property.onlyWritten
        private ContentManagerInterface $contentManager,
    ) {
    }

    public function cgetAction(Request $request, string $id): JsonResponse
    {
        $listKey = $request->query->get('listKey');
        $locale = $request->query->get('locale');
        if (!$listKey) {
            throw new \InvalidArgumentException('The "listKey" query parameter is required.');
        }

        /** @var DoctrineFieldDescriptor[] $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors($listKey);
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->listBuilderFactory->create($fieldDescriptors['id']->getEntityName());
        $listBuilder->setParameter('locale', $locale);
        $listBuilder->setIdField($fieldDescriptors['id']); // TODO should be uuid field descriptor
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listBuilder->where($fieldDescriptors['id'], $id, ListBuilderInterface::WHERE_COMPARATOR_EQUAL);

        $result = $listBuilder->execute();
        $listRepresentation = new PaginatedRepresentation(
            $result,
            'versions',
            $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            $listBuilder->count(),
        );

        return new JsonResponse(
            $this->normalizer->normalize(
                $listRepresentation->toArray(),
                'json',
                ['sulu_admin' => true, 'sulu_admin_page' => true, 'sulu_admin_page_list' => true],
            )
        );
    }

    public function postTriggerAction(Request $request, string $id): Response
    {
        $result = $this->handleAction($request, $id);

        // TODO the `$contentRichEntity` should just be serialized
        //      Instead of calling the content resolver service which triggers an additional query.
        $dimensionContent = $this->contentManager->resolve($result, [
            'locale' => $request->query->get('locale'),
            'stage' => DimensionContentInterface::STAGE_DRAFT,
        ]);
        $normalizedContent = $this->contentManager->normalize($dimensionContent);

        return new JsonResponse($this->normalizer->normalize(
            $normalizedContent, // TODO this should just be the page entity see comment above
            'json',
        ));
    }

    private function handleAction(Request $request, string $uuid): ?ContentRichEntityInterface // @phpstan-ignore-line
    {
        $action = $request->query->get('action');

        if (!$action) {
            throw new \InvalidArgumentException('The "action" query parameter is required.');
        }

        if ('restore' === $action) {
            $version = \intval($request->query->get('version'));
            if (!$version) {
                throw new \InvalidArgumentException('The "version" query parameter is required for restoring a version.');
            }
            $type = $request->query->get('type');
            if (!$type) {
                throw new \InvalidArgumentException('The "type" query parameter is required for restoring a version.');
            }

            $message = new RestoreContentVersionMessage(
                ['uuid' => $uuid],
                $version,
                $type,
                $request->query->all(),
            );

            /** @see Sulu\Content\Application\MessageHandler\RestoreContentVersionMessageHandler */
            /** @var ContentRichEntityInterface|null */
            return $this->handle(new Envelope($message, [new EnableFlushStamp()]));
        }

        throw new \InvalidArgumentException(\sprintf('Unknown action "%s".', $action));
    }
}
