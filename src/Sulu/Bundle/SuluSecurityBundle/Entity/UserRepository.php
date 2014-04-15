<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Sulu\Component\Security\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Query;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects
 */
class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findUserById($id)
    {
        try {

            $qb = $this->createQueryBuilder('user')
                ->leftJoin('user.userRoles', 'userRoles')
                ->leftJoin('userRoles.role', 'role')
                ->leftJoin('user.userGroups', 'userGroups')
                ->leftJoin('user.userSettings', 'settings')
                ->leftJoin('userGroups.group', 'grp')
                ->leftJoin('user.contact', 'contact')
                ->leftJoin('contact.emails', 'emails')
                ->addSelect('userRoles')
                ->addSelect('role')
                ->addSelect('userGroups')
                ->addSelect('grp')
                ->addSelect('settings')
                ->addSelect('contact')
                ->addSelect('emails')
                ->where('user.id=:userId');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('userId', $id);

            return $query->getSingleResult();

        } catch (NoResultException $ex) {
            return null;
        }
    }

    /**
     * Searches for a user with a specific contact id
     * @param $id
     * @return array
     */
    public function findUserByContact($id)
    {
        try {

            $qb = $this->createQueryBuilder('user')
                ->leftJoin('user.userRoles', 'userRoles')
                ->leftJoin('userRoles.role', 'role')
                ->leftJoin('user.userGroups', 'userGroups')
                ->leftJoin('user.userSettings', 'settings')
                ->leftJoin('userGroups.group', 'grp')
                ->leftJoin('user.contact', 'contact')
                ->leftJoin('contact.emails', 'emails')
                ->addSelect('userRoles')
                ->addSelect('role')
                ->addSelect('userGroups')
                ->addSelect('grp')
                ->addSelect('settings')
                ->addSelect('contact')
                ->addSelect('emails')
                ->where('user.contact=:contactId');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('contactId', $id);

            return $query->getSingleResult();

        } catch (NoResultException $ex) {
            return null;
        }
    }

    /**
     * Loads the user for the given username.
     *
     * This method throws UsernameNotFoundException if the user is not found.
     *
     * @param string $username The username
     * @return UserInterface
     * @see UsernameNotFoundException
     * @throws UsernameNotFoundException if the user is not found
     *
     */
    public function loadUserByUsername($username)
    {

        $qb = $this->createQueryBuilder('user')
            ->leftJoin('user.userRoles', 'userRoles')
            ->leftJoin('userRoles.role', 'role')
            ->leftJoin('role.permissions', 'permissions')
            ->leftJoin('user.userGroups', 'userGroups')
            ->leftJoin('userGroups.group', 'grp')
            ->leftJoin('user.userSettings', 'settings')
            ->addSelect('userRoles')
            ->addSelect('role')
            ->addSelect('userGroups')
            ->addSelect('grp')
            ->addSelect('settings')
            ->addSelect('permissions')
            ->where('user.username=:username');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $query->setParameter('username', $username);


        try {
            return $query->getSingleResult();
        } catch (NoResultException $nre) {
            $message = sprintf(
                'Unable to find an SuluSecurityBundle:User object identified by %s',
                $username
            );
            throw new UsernameNotFoundException($message, 0, $nre);
        }

    }

    /**
     * Refreshes the user for the account interface.
     *
     * @param UserInterface $user
     * @return UserInterface
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instance of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->find($user->getId());
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    /**
     * Sets the security system
     * @param string $system
     */
    public function setSystem($system)
    {
        // TODO: Implement setSystem() method.
    }

    /**
     * Returns the security system
     * @return string
     */
    public function getSystem()
    {
        // TODO: Implement getSystem() method.
    }
}
