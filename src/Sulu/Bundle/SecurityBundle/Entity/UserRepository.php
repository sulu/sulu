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
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects.
 */
class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * The standard sulu system.
     *
     * @var string
     */
    private $suluSystem;

    /**
     * initializes the UserRepository.
     */
    public function init($suluSystem, RequestAnalyzerInterface $requestAnalyzer = null)
    {
        $this->suluSystem = $suluSystem;
        $this->requestAnalyzer = $requestAnalyzer;
    }

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
            $query->setParameter('userId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * Searches for a user with a specific contact id.
     *
     * @param $id
     *
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
            $query->setParameter('contactId', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return;
        }
    }

    /**
     * Loads the user for the given username or email.
     *
     * @param string $identifier The username or email of the user to load
     *
     * @throws LockedException if the User is Locked
     * @throws DisabledException if the User is not active
     * @throws UsernameNotFoundException if the User is not found
     *
     * @return UserInterface
     */
    public function loadUserByUsername($identifier)
    {
        try {
            /** @var User $user */
            $user = $this->findUserByIdentifier($identifier);

            if (!$user->getEnabled()) {
                throw new DisabledException();
            }

            if ($user->getLocked()) {
                throw new LockedException();
            }

            foreach ($user->getUserRoles() as $ur) {
                /** @var UserRole $ur */
                if ($ur->getRole()->getSystem() == $this->getSystem()) {
                    return $user;
                }
            }
            throw new NoResultException();
        } catch (NoResultException $nre) {
            $message = sprintf(
                'Unable to find an SuluSecurityBundle:User object identified by %s',
                $identifier
            );

            throw new UsernameNotFoundException($message, 0, $nre);
        }
    }

    /**
     * Finds a user for the given username.
     *
     * This method throws UsernameNotFoundException if the user is not found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByUsername($username)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.username=:username');

        $query = $qb->getQuery();
        $query->setParameter('username', $username);

        return $query->getSingleResult();
    }

    /**
     * Finds a user for a given email.
     *
     * @param string $email The email-address
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByEmail($email)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.email=:email');

        $query = $qb->getQuery();
        $query->setParameter('email', $email);

        return $query->getSingleResult();
    }

    /**
     * Finds a user for a given password-reset-token.
     *
     * @param string $token the reset-token
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByToken($token)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.passwordResetToken=:token');

        $query = $qb->getQuery();
        $query->setParameter('token', $token);

        return $query->getSingleResult();
    }

    /**
     * Finds a user for a given email or username.
     *
     * @param string $identifier The email-address or username
     *
     * @return UserInterface
     *
     * @throws NoResultException if the user is not found
     */
    public function findUserByIdentifier($identifier)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.email=:email')
            ->orWhere('user.username=:username');

        $query = $qb->getQuery();
        $query->setParameter('email', $identifier);
        $query->setParameter('username', $identifier);

        return $query->getSingleResult();
    }

    /**
     * Refreshes the user for the account interface.
     *
     * @param UserInterface $user
     *
     * @return UserInterface
     *
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
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    /**
     * Sets the request analyzer.
     *
     * @param \Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface $requestAnalyzer
     */
    public function setRequestAnalyzer(RequestAnalyzerInterface $requestAnalyzer)
    {
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * Returns the security system.
     *
     * @return string
     */
    protected function getSystem()
    {
        $system = $this->suluSystem;
        if (
            $this->requestAnalyzer != null &&
            $this->requestAnalyzer->getWebspace() !== null &&
            $this->requestAnalyzer->getWebspace()->getSecurity() !== null
        ) {
            // if the request analyzer is available, overwrite the system
            $system = $this->requestAnalyzer->getWebspace()->getSecurity()->getSystem();
        }

        return $system;
    }

    /**
     * returns username for given apiKey.
     *
     * @param string $apiKey userId
     *
     * @return string
     */
    public function getUsernameByApiKey($apiKey)
    {
        $user = $this->findOneBy(array('apiKey' => $apiKey));
        if (!$user) {
            return;
        }

        return $user->getUsername();
    }

    /**
     * returns all users within the defined system including their contacts.
     */
    public function getUserInSystem()
    {
        $qb = $this->createQueryBuilder('user')
            ->select('user', 'contact')
            ->leftJoin('user.userRoles', 'userRoles')
            ->leftJoin('user.contact', 'contact')
            ->leftJoin('userRoles.role', 'role')
            ->where('role.system=:system');

        $query = $qb->getQuery();
        $query->setParameter('system', $this->getSystem());

        try {
            return $query->getResult();
        } catch (NoResultException $nre) {
            $message = 'Unable to find any SuluSecurityBundle:User object with a contact';
            throw new UsernameNotFoundException($message, 0, $nre);
        }
    }
}
