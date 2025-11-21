<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Security;

use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Rest\Exception\InsufficientDescendantPermissionsException;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal this listener should not be extended by a project
 */
final class PageDescendantSecurityListener implements EventSubscriberInterface
{
    /**
     * @param array<string, int> $permissions
     */
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private AccessControlRepositoryInterface $accessControlRepository,
        private SystemStoreInterface $systemStore,
        private ?Security $security,
        private array $permissions,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -10], // After SuluSecurityListener (priority 0)
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if ('sulu_page.delete_page' !== $request->attributes->get('_route')) {
            return;
        }

        if (null === $this->security) {
            return;
        }

        if ($request->query->getBoolean('deleteLocale', false)) {
            return;
        }

        $id = $request->attributes->get('id');
        if (null === $id || !\is_string($id)) {
            return;
        }

        $page = $this->pageRepository->getOneBy(['uuid' => $id]);

        $this->checkPermissionsForDescendants($page);
    }

    private function checkPermissionsForDescendants(PageInterface $page): void
    {
        /** @var UserInterface|null $user */
        $user = $this->security?->getUser();
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
}
