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

namespace Sulu\Search\UserInterface\Controller\Admin;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Search\Condition\Condition;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Rest\ListBuilder\ListRestHelperInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SearchController
{
    /**
     * @param mixed[] $resources
     */
    public function __construct(
        private readonly EngineInterface $engine,
        private readonly ListRestHelperInterface $listRestHelper,
        private readonly array $resources,
        private readonly MediaManagerInterface $mediaManager,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function queryAction(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $resourceKey = $request->query->get('resourceKey', null);
        $locale = $request->query->get('locale', null);

        /** @var string $page */
        $page = $this->listRestHelper->getPage();
        $limit = $this->listRestHelper->getLimit();
        $offset = $this->listRestHelper->getOffset() ?: 0;

        /** @var User $user */
        $user = $this->tokenStorage->getToken()?->getUser();
        $userLocale = $user->getLocale();

        $search = $this->engine->createSearchBuilder('admin')
            ->addFilter(Condition::search($query))
            ->limit((int) $limit)
            ->offset($offset);

        if ($resourceKey) {
            $search->addFilter(Condition::equal('resourceKey', $resourceKey));
        }

        if ($locale) {
            $search->addFilter(Condition::equal('locale', $locale));
        }

        $result = $search->getResult();
        $documents = [];
        $mediaIds = [];

        /** @var array{id: string, mediaId?: string|null } $document */
        foreach ($result as $document) {
            $documents[$document['id']] = $document;

            if ($document['mediaId'] ?? null) {
                $mediaIds[$document['id']] = $document['mediaId'];
            }
        }

        if (!empty($mediaIds)) {
            /** @var array<string, array<string, string>> $medias */
            $medias = $this->mediaManager->getFormatUrls($mediaIds, $userLocale);

            foreach ($mediaIds as $documentId => $mediaId) {
                if (isset($medias[$mediaId]['sulu-100x100'])) {
                    $documents[$documentId]['imageUrl'] = $medias[$mediaId]['sulu-100x100'];
                }
            }
        }

        $documents = \array_values($documents);

        $representation = new PaginatedRepresentation(
            $documents,
            'result',
            (int) $page,
            (int) $limit,
            $result->total(),
        );

        return new JsonResponse($representation->toArray());
    }

    public function indexAction(): JsonResponse
    {
        $searchResources = [];

        /**
         * @var string $resourceKey
         * @var mixed[] $resource
         */
        foreach ($this->resources as $resourceKey => $resource) {
            $searchResources[$resourceKey] = [
                'resourceKey' => $resourceKey,
                ...$resource,
            ];
        }

        return new JsonResponse([
            '_embedded' => [
                'search_resources' => $searchResources,
            ],
        ]);
    }
}
