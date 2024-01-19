<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Defines the interface for a UserRepository.
 *
 * @extends RepositoryInterface<UserInterface>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns the user with the given id.
     *
     * @param int $id The user to find
     *
     * @return UserInterface|null
     */
    public function findUserById($id);

    /**
     * Returns the users with the given ids.
     *
     * @param array $ids The ids of the user to load
     *
     * @return UserInterface[]
     */
    public function findUsersById(array $ids);

    /**
     * @param int $id
     *
     * @return UserInterface
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findUserWithSecurityById($id);

    /**
     * Finds a user for a given email or username.
     *
     * @param string $identifier The email-address or username
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByIdentifier($identifier);
}
