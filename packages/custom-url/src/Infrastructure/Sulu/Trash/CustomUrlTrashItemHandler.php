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

namespace Sulu\CustomUrl\Infrastructure\Sulu\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Domain\Event\CustomUrlRestoredEvent;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Webmozart\Assert\Assert;

/** @phpstan-type TrashRestoreData array{
 * title: string,
 * creator: UserInterface,
 * created: string,
 * changer: string,
 * changed: string,
 * baseDomain: string,
 * domainParts: array<string>,
 * canonical: bool,
 * redirect: bool,
 * noFollow: bool,
 * noIndex: bool,
 * targetUuid: string,
 * targetLocale: string,
 * webspaceKey: string,
 * }
 */
final class CustomUrlTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private CustomUrlMapperInterface $customUrlMapper,
        private TrashItemRepositoryInterface $trashItemRepository,
        private DocumentDomainEventCollectorInterface $documentDomainEventCollector,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function store(object $customUrl, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($customUrl, CustomUrlInterface::class);

        $data = [
            'title' => $customUrl->getTitle(),
            'creator' => $customUrl->getCreator(),
            'created' => $customUrl->getCreated()->format('c'),
            'changer' => $customUrl->getChanger(),
            'changed' => $customUrl->getChanged()->format('c'),
            'baseDomain' => $customUrl->getBaseDomain(),
            'domainParts' => $customUrl->getDomainParts(),
            'canonical' => $customUrl->isCanonical(),
            'redirect' => $customUrl->isRedirect(),
            'noFollow' => $customUrl->isNoFollow(),
            'noIndex' => $customUrl->isNoIndex(),
            'targetUuid' => $customUrl->getTargetDocument(),
            'targetLocale' => $customUrl->getTargetLocale(),
            'webspaceKey' => $customUrl->getWebspace(),
        ];

        return $this->trashItemRepository->create(
            CustomUrl::RESOURCE_KEY,
            (string) $customUrl->getId(),
            $customUrl->getTitle(),
            $data,
            null,
            $options,
            CustomUrlAdmin::getCustomUrlSecurityContext($customUrl->getWebspace()),
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = $trashItem->getResourceId();

        /** @var TrashRestoreData $data */
        $data = $trashItem->getRestoreData();
        $data['published'] = false;

        $customUrl = $this->customUrlRepository->create();
        $customUrl->setId($id);
        $this->customUrlMapper->mapCustomUrlData($customUrl, $data);

        $customUrl->setCreator($data['creator']);
        $customUrl->setCreated(new \DateTime($data['created']));

        $this->entityManager->persist($customUrl);
        $this->documentDomainEventCollector->collect(new CustomUrlRestoredEvent($customUrl, $data));
        $this->entityManager->flush();

        return $customUrl;
    }

    public static function getResourceKey(): string
    {
        return CustomUrlInterface::RESOURCE_KEY;
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(
            null,
            CustomUrlAdmin::LIST_VIEW,
            ['webspace' => 'webspace']
        );
    }
}
