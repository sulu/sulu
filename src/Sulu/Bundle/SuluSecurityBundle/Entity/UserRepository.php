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
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects
 */
class UserRepository extends EntityRepository implements UserProviderInterface
{

    /**
     * Searches for a user with a specific contact id
     * @param $id
     * @return array
     */
    public function findUserByContact($id)
    {
        try {
        $dql = 'SELECT user, userRoles, role
				FROM SuluSecurityBundle:User user
                    LEFT JOIN user.userRoles userRoles
                    LEFT JOIN userRoles.role role
				WHERE user.contact = :contactId';

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(
                array(
                    'contactId' => $id
                )
            );

        $result = $query->getSingleResult();

        return $result;

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
        $dql = '
            SELECT u, ur, r, p
                FROM SuluSecurityBundle:User u
                    LEFT JOIN u.userRoles ur
                    LEFT JOIN ur.role r
                    LEFT JOIN r.permissions p
                WHERE u.username = :username';

        $query = $this->getEntityManager()->createQuery($dql);

        try {
            $user = $query->setParameter('username', $username)->getSingleResult();
        } catch (NoResultException $nre) {
            $message = sprintf(
                'Unable to find an SuluSecurityBundle:User object identified by %s',
                $username
            );
            throw new UsernameNotFoundException($message, 0, $nre);
        }

        return $user;
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
}
