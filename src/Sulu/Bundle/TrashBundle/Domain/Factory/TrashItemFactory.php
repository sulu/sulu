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

namespace Sulu\Bundle\TrashBundle\Domain\Factory;

use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Security;

final class TrashItemFactory implements TrashItemFactoryInterface
{
    /**
     * @var Security|null
     */
    private $security;

    /**
     * @var class-string<TrashItemInterface>
     */
    private $trashItemClass;

    /**
     * @param class-string<TrashItemInterface> $trashItemClass
     */
    public function __construct(?Security $security, string $trashItemClass)
    {
        $this->security = $security;
        $this->trashItemClass = $trashItemClass;
    }

    public function create(
        string $resourceKey,
        string $resourceId,
        array $restoreData,
        $resourceTitle,
        ?string $resourceSecurityContext,
        ?string $resourceSecurityObjectType,
        ?string $resourceSecurityObjectId
    ): TrashItemInterface {
        /** @var TrashItemInterface $trashItem */
        $trashItem = new $this->trashItemClass();

        $trashItem
            ->setResourceKey($resourceKey)
            ->setResourceId($resourceId)
            ->setRestoreData($restoreData)
            ->setResourceSecurityContext($resourceSecurityContext)
            ->setResourceSecurityObjectType($resourceSecurityObjectType)
            ->setResourceSecurityObjectId($resourceSecurityObjectId)
            ->setTimestamp(new \DateTimeImmutable())
            ->setUser($this->getCurrentUser());

        if (\is_string($resourceTitle)) {
            $trashItem->setResourceTitle($resourceTitle);
        }

        if (\is_array($resourceTitle)) {
            foreach ($resourceTitle as $locale => $title) {
                $trashItem->setResourceTitle($title, $locale);
            }
        }

        return $trashItem;
    }

    private function getCurrentUser(): ?UserInterface
    {
        if (null === $this->security) {
            return null;
        }

        /** @var UserInterface $user */
        $user = $this->security->getUser();

        return $user;
    }
}
