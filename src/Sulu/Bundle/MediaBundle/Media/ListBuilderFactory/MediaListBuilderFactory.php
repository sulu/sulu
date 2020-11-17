<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ListBuilderFactory;

use Sulu\Bundle\MediaBundle\Entity\CollectionRepositoryInterface;
use Sulu\Component\Media\SystemCollections\SystemCollectionManagerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilder;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class MediaListBuilderFactory
{
    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $doctrineListBuilderFactory;

    /**
     * @var CollectionRepositoryInterface
     */
    private $collectionRepository;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var string
     */
    private $mediaClass;

    /**
     * @var string
     */
    private $collectionClass;

    public function __construct(
        RestHelperInterface $restHelper,
        DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        CollectionRepositoryInterface $collectionRepository,
        SecurityCheckerInterface $securityChecker,
        string $mediaClass,
        string $collectionClass
    ) {
        $this->restHelper = $restHelper;
        $this->doctrineListBuilderFactory = $doctrineListBuilderFactory;
        $this->collectionRepository = $collectionRepository;
        $this->securityChecker = $securityChecker;
        $this->mediaClass = $mediaClass;
        $this->collectionClass = $collectionClass;
    }

    public function getListBuilder(
        array $fieldDescriptors,
        UserInterface $user,
        array $types = [],
        bool $sortByDefault = true,
        ?int $collectionId = null
    ): DoctrineListBuilder {
        /** @var DoctrineListBuilder $listBuilder */
        $listBuilder = $this->doctrineListBuilderFactory->create($this->mediaClass);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        // default sort by created
        if ($sortByDefault) {
            $listBuilder->sort($fieldDescriptors['created'], 'desc');
        }

        /** @var DoctrineFieldDescriptor $collectionFieldDescriptor */
        $collectionFieldDescriptor = $fieldDescriptors['collection'];

        if ($collectionId) {
            $collectionType = $this->collectionRepository->findCollectionTypeById($collectionId);
            if (SystemCollectionManagerInterface::COLLECTION_TYPE === $collectionType) {
                $this->securityChecker->checkPermission(
                    'sulu.media.system_collections',
                    PermissionTypes::VIEW
                );
            }
            $listBuilder->addSelectField($collectionFieldDescriptor);
            $listBuilder->where($collectionFieldDescriptor, $collectionId);
        } else {
            $listBuilder->addPermissionCheckField($collectionFieldDescriptor);
            $listBuilder->setPermissionCheck(
                $user,
                PermissionTypes::VIEW,
                $this->collectionClass
            );
        }

        // set the types
        if (\count($types)) {
            $listBuilder->in($fieldDescriptors['type'], $types);
        }

        if (!$this->securityChecker->hasPermission('sulu.media.system_collections', PermissionTypes::VIEW)) {
            $systemCollection = $this->collectionRepository
                ->findCollectionByKey(SystemCollectionManagerInterface::COLLECTION_KEY);

            $lftExpression = $listBuilder->createWhereExpression(
                $fieldDescriptors['lft'],
                $systemCollection->getLft(),
                ListBuilderInterface::WHERE_COMPARATOR_LESS
            );
            $rgtExpression = $listBuilder->createWhereExpression(
                $fieldDescriptors['rgt'],
                $systemCollection->getRgt(),
                ListBuilderInterface::WHERE_COMPARATOR_GREATER
            );

            $listBuilder->addExpression(
                $listBuilder->createOrExpression([
                    $lftExpression,
                    $rgtExpression,
                ])
            );
        }

        // field which will be needed afterwards to generate route
        $listBuilder->addSelectField($fieldDescriptors['previewImageId']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageName']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageVersion']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageSubVersion']);
        $listBuilder->addSelectField($fieldDescriptors['previewImageMimeType']);
        $listBuilder->addSelectField($fieldDescriptors['version']);
        $listBuilder->addSelectField($fieldDescriptors['subVersion']);
        $listBuilder->addSelectField($fieldDescriptors['name']);
        $listBuilder->addSelectField($fieldDescriptors['locale']);
        $listBuilder->addSelectField($fieldDescriptors['mimeType']);
        $listBuilder->addSelectField($fieldDescriptors['storageOptions']);
        $listBuilder->addSelectField($fieldDescriptors['id']);
        $listBuilder->addSelectField($fieldDescriptors['collection']);

        return $listBuilder;
    }
}
