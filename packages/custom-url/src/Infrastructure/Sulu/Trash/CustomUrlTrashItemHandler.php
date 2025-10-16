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
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
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
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Webmozart\Assert\Assert;

/** @phpstan-type TrashRestoreData array{
 *     title: string,
 *     published: bool,
 *     baseDomain: string,
 *     domainParts: array<string>,
 *     targetLocale: string,
 *     targetDocument: null|string,
 *     redirect: bool,
 *     canonical: bool,
 *     noFollow: bool,
 *     noIndex: bool,
 *     targetUuid: string,
 *     targetLocale: string,
 *     webspaceKey: string,
 *     creator: null|string,
 *     created: string,
 *     changer: null|string,
 *     changed: string,
 * }
 */
final class CustomUrlTrashItemHandler implements
    StoreTrashItemHandlerInterface,
    RestoreTrashItemHandlerInterface,
    RestoreConfigurationProviderInterface
{
    /**
     * @param iterable<CustomUrlMapperInterface> $customUrlMappers
     */
    public function __construct(
        private CustomUrlRepositoryInterface $customUrlRepository,
        private readonly iterable $customUrlMappers,
        private TrashItemRepositoryInterface $trashItemRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function store(object $customUrl, array $options = []): TrashItemInterface
    {
        Assert::isInstanceOf($customUrl, CustomUrlInterface::class);

        /** @var TrashRestoreData $data */
        $data = [
            'title' => $customUrl->getTitle(),
            'published' => $customUrl->isPublished(),
            'baseDomain' => $customUrl->getBaseDomain(),
            'domainParts' => $customUrl->getDomainParts(),
            'targetLocale' => $customUrl->getTargetLocale(),
            'targetDocument' => $customUrl->getTargetDocument(),
            'canonical' => $customUrl->isCanonical(),
            'redirect' => $customUrl->isRedirect(),
            'noFollow' => $customUrl->isNoFollow(),
            'noIndex' => $customUrl->isNoIndex(),
            'webspaceKey' => $customUrl->getWebspace(),
            'creator' => $customUrl->getCreator()?->getId(),
            'created' => $customUrl->getCreated()->format('c'),
            'changer' => $customUrl->getChanger()?->getId(),
            'changed' => $customUrl->getChanged()->format('c'),
        ];

        return $this->trashItemRepository->create(
            CustomUrl::RESOURCE_KEY,
            (string) $customUrl->getUuid(),
            $customUrl->getTitle(),
            $data,
            null,
            $options,
            CustomUrlAdmin::getCustomUrlSecurityContext($customUrl->getWebspace()),
            null,
            null,
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $id = $trashItem->getResourceId();

        /** @var TrashRestoreData $data */
        $data = $trashItem->getRestoreData();

        $customUrl = $this->customUrlRepository->createNew($id);
        $customUrl->setWebspace($data['webspaceKey']);
        foreach ($this->customUrlMappers as $customUrlMapper) {
            $customUrlMapper->mapCustomUrlData($customUrl, $data);
        }

        if (isset($data['creator'])) {
            $customUrl->setCreator($this->entityManager->getReference(UserInterface::class, $data['creator']));
        }
        $customUrl->setCreated(new \DateTimeImmutable($data['created']));

        if (isset($data['changer'])) {
            $customUrl->setChanger($this->entityManager->getReference(UserInterface::class, $data['changer']));
        }
        $customUrl->setChanged(new \DateTimeImmutable($data['changed']));

        $this->entityManager->persist($customUrl);
        $this->domainEventCollector->collect(new CustomUrlRestoredEvent($customUrl, $data));
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
            ['webspace' => 'webspace'],
        );
    }
}
