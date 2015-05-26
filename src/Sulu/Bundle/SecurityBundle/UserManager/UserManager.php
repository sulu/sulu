<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\UserManager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Sulu\Bundle\AdminBundle\UserManager\CurrentUserDataInterface;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;

class UserManager implements UserManagerInterface
{
    /**
     * @var Registry
     */
    private $doctrine;
    /**
     * @var CurrentUserDataInterface
     */
    private $currentUserData;

    public function __construct(Registry $doctrine, CurrentUserDataInterface $currentUserData = null)
    {
        $this->doctrine = $doctrine;
        $this->currentUserData = $currentUserData;
    }

    /**
     * returns user for given id.
     *
     * @param int $id userId
     *
     * @return User
     */
    public function getUserById($id)
    {
        /** @var ObjectRepository $repository */
        $repository = $this->doctrine->getRepository('SuluSecurityBundle:User');

        return $repository->find($id);
    }

    /**
     * returns username for given id.
     *
     * @param int $id userId
     *
     * @return string
     */
    public function getUsernameByUserId($id)
    {
        return $this->getUserById($id)->getUsername();
    }

    /**
     * returns fullName for given id.
     *
     * @param int $id userId
     *
     * @return string
     */
    public function getFullNameByUserId($id)
    {
        return $this->getUserById($id)->getFullName();
    }

    /**
     * returns user data of current user.
     *
     * @return CurrentUserDataInterface
     */
    public function getCurrentUserData()
    {
        return $this->currentUserData;
    }
}
