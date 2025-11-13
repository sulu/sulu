<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\MessageHandler;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\Exception\InsufficientDescendantPermissionsException;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Page\Application\Message\RemovePageMessage;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Exception\RemovePageDependantResourcesFoundException;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @experimental
 *
 * @internal This class should not be instantiated by a project.
 *           Create your own Message and Handler instead.
 */
final class RemovePageMessageHandler
{
    /**
     * @param array<string, int> $permissions
     */
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private AccessControlRepositoryInterface $accessControlRepository,
        private SystemStoreInterface $systemStore,
        private ?Security $security,
        private array $permissions,
        private ?TrashManagerInterface $trashManager = null,
    ) {
    }

    public function __invoke(RemovePageMessage $message): void
    {
        $page = $this->pageRepository->getOneBy($message->getIdentifier());

        $this->checkPermissionsForDescendants($page);

        if (!$message->getForceRemoveChildren()) {
            $this->checkDescendantsForDependencyWarning($page);
        }

        $this->pageRepository->remove($page);

        /** @var string $resourceKey */
        $resourceKey = $page::RESOURCE_KEY;
        $this->trashManager?->store($resourceKey, $page);

        $dimensionContentCollection = new DimensionContentCollection($page->getDimensionContents()->toArray(), [], PageDimensionContent::class);
        /** @var PageDimensionContentInterface|null $localizedDimensionContent */
        $localizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $message->getLocale()]);
        $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'draft']);
        $context = $unlocalizedDimensionContent?->getAvailableLocales() ? ['locales' => $unlocalizedDimensionContent->getAvailableLocales()] : [];

        // Try to get title from the removed locale first, fallback to any available locale if null
        $title = $localizedDimensionContent?->getTitle();
        if (null === $title && $unlocalizedDimensionContent) {
            $availableLocales = $unlocalizedDimensionContent->getAvailableLocales() ?? [];
            foreach ($availableLocales as $availableLocale) {
                $fallbackDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => $availableLocale]);
                if ($fallbackDimensionContent instanceof PageDimensionContentInterface && null !== $fallbackDimensionContent->getTitle()) {
                    $title = $fallbackDimensionContent->getTitle();
                    break;
                }
            }
        }

        $this->domainEventCollector->collect(new PageRemovedEvent(
            $page->getUuid(),
            $page->getWebspaceKey(),
            $title,
            $context
        ));
    }

    private function checkPermissionsForDescendants(PageInterface $page): void
    {
        $user = $this->getCurrentUser();
        if (null === $user) {
            return;
        }

        $descendantIds = $this->pageRepository->findDescendantIdsById($page->getUuid());
        if (empty($descendantIds)) {
            return;
        }

        $authorizedDescendantIds = $this->accessControlRepository->findIdsWithGrantedPermissions(
            $user,
            $this->permissions[PermissionTypes::DELETE],
            Page::class,
            $descendantIds,
            $this->systemStore->getSystem(),
            null
        );

        $unauthorizedDescendantIds = \array_diff($descendantIds, $authorizedDescendantIds);
        if (!empty($unauthorizedDescendantIds)) {
            throw new InsufficientDescendantPermissionsException(
                \count($unauthorizedDescendantIds),
                PermissionTypes::DELETE
            );
        }
    }

    private function checkDescendantsForDependencyWarning(PageInterface $page): void
    {
        $descendantIds = $this->pageRepository->findDescendantIdsById($page->getUuid());

        if (empty($descendantIds)) {
            return;
        }

        /** @var array<string> $descendantIds */
        $descendants = $this->pageRepository->findBy(['uuids' => $descendantIds]);
        $descendantResources = [];
        foreach ($descendants as $descendant) {
            $descendantResources[] = [
                'uuid' => $descendant->getUuid(),
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'depth' => $descendant->getDepth(),
            ];
        }

        throw new RemovePageDependantResourcesFoundException(
            [
                'id' => $page->getUuid(),
                'resourceKey' => PageInterface::RESOURCE_KEY,
            ],
            $this->groupResourcesByDepth($descendantResources),
            \count($descendantResources)
        );
    }

    /**
     * @param array<array{uuid: string, resourceKey: string, depth: int}> $resources
     *
     * @return array<int, array<array{id: string, resourceKey: string}>>
     */
    private function groupResourcesByDepth(array $resources): array
    {
        $grouped = [];

        foreach ($resources as $resource) {
            $depth = $resource['depth'];

            if (!isset($grouped[$depth])) {
                $grouped[$depth] = [];
            }

            $grouped[$depth][] = [
                'id' => $resource['uuid'],
                'resourceKey' => $resource['resourceKey'],
            ];
        }

        \krsort($grouped);

        return \array_values($grouped);
    }

    private function getCurrentUser(): ?UserInterface
    {
        if (null === $this->security) {
            return null;
        }

        /** @var UserInterface|null $user */
        $user = $this->security->getUser();

        return $user;
    }
}
