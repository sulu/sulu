<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Services;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Sulu\Bundle\SecurityBundle\Entity\User;

class UserService implements UserServiceInterface
{

    /**
     * @var Registry
     */
    private $doctrine;

    function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * returns user for given id
     * @param integer $id userId
     * @return User
     */
    public function getUserById($id)
    {
        /** @var ObjectRepository $repository */
        $repository = $this->doctrine->getRepository('SuluSecurityBundle:User');

        return $repository->find($id);
    }

    /**
     * returns username by id
     * @param integer $id userId
     * @return string
     */
    public function getUsernameByUserId($id)
    {
        return $this->getUserById($id)->getUsername();
    }

    /**
     * returns fullName for userId
     * @param integer $id userId
     * @return string
     */
    public function getFullNameByUserId($id)
    {
        return $this->getUserById($id)->getFullName();
    }
}
