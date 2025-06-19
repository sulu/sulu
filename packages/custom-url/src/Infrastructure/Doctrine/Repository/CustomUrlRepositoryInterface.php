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

namespace Sulu\CustomUrl\Infrastructure\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Persistence\ObjectRepository;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;

/**
 * @extends ObjectRepository<CustomUrlInterface>
 */
interface CustomUrlRepositoryInterface extends ServiceEntityRepositoryInterface, ObjectRepository
{
    public function createNew(?string $uuid = null): CustomUrlInterface;

    public function add(CustomUrlInterface $customUrl): void;

    /**
     * @return array<CustomUrlInterface>
     */
    public function findByTarget(UuidBehavior $page): array;

    public function remove(CustomUrlInterface $customUrl): void;

    public function findByUrlNewestPublished(string $url, ?string $locale = null): ?CustomUrlInterface;
}
