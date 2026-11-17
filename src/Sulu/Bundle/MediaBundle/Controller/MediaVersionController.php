<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lists the file versions of a single media.
 */
class MediaVersionController extends AbstractRestController
{
    public const LIST_KEY = 'media_versions';

    public const RESOURCE_KEY = 'media_versions';

    public function __construct(
        ViewHandlerInterface $viewHandler,
        private MediaManagerInterface $mediaManager,
        private SecurityCheckerInterface $securityChecker,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
    ) {
        parent::__construct($viewHandler);
    }

    public function cgetAction(int $id, Request $request): Response
    {
        $locale = $this->getLocale($request);

        try {
            $media = $this->mediaManager->getEntityById($id);
        } catch (MediaNotFoundException $e) {
            return $this->handleView($this->view($e->toArray(), 404));
        }

        $collection = $media->getCollection();
        if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collection->getType()->getKey()) {
            $this->securityChecker->checkPermission('sulu.media.system_collections', PermissionTypes::VIEW);
        }

        $this->securityChecker->checkPermission(
            new SecurityCondition(MediaAdmin::SECURITY_CONTEXT, $locale, Collection::class, $collection->getId()),
            PermissionTypes::VIEW
        );

        /** @var array{version: FieldDescriptorInterface, name: FieldDescriptorInterface, mediaId: FieldDescriptorInterface} $fieldDescriptors */
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(self::LIST_KEY);
        $listBuilder = $this->listBuilderFactory->create(FileVersion::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // The urls are built from these, so they are selected even when their columns are hidden.
        $listBuilder->addSelectField($fieldDescriptors['version']);
        $listBuilder->addSelectField($fieldDescriptors['name']);
        $listBuilder->where($fieldDescriptors['mediaId'], $id);
        // Bound only if a join uses it, so a project's list override can join translated metadata on :locale.
        $listBuilder->setParameter('locale', $locale);

        if (!$request->query->get('sortBy')) {
            $listBuilder->sort($fieldDescriptors['version'], ListBuilderInterface::SORTORDER_DESC);
        }

        $currentVersion = ($media->getFiles()[0] ?? null)?->getVersion();

        /** @var list<array{version: int, name: string}> $rows */
        $rows = $listBuilder->execute();

        $versions = [];
        foreach ($rows as $version) {
            $version['url'] = $this->mediaManager->getUrl($id, $version['name'], $version['version']);
            $version['adminUrl'] = $this->mediaManager->getAdminUrl($id, $version['name'], $version['version']);
            $version['current'] = $currentVersion === $version['version'];

            $versions[] = $version;
        }

        return $this->handleView($this->view(new PaginatedRepresentation(
            $versions,
            self::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count()
        )));
    }
}
