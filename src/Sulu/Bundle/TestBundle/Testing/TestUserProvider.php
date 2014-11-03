<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\TestBundle\Entity\TestContact;
use Sulu\Bundle\TestBundle\Entity\TestUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * An UserProvider which returns always the same user for testing purposes
 * @package Sulu\Component\Testing
 */
class TestUserProvider implements UserProviderInterface
{
    /**
     * @var TestUser
     */
    private $user;

    public function __construct(ObjectManager $em)
    {
        $this->user = $em->getRepository('Sulu\Bundle\TestBundle\Entity\TestUser')->findOneByUsername('test');
        if (!$this->user) {
            $contact = new TestContact();
            $contact->setFirstName('Max');
            $contact->setLastName('Mustermann');
            $em->persist($contact);
            $this->user = new TestUser();
            $this->user->setPassword('test');
            $this->user->setUsername('test');
            $this->user->setLocale('en');
            $this->user->setContact($contact);

            $em->persist($this->user);
            $em->flush($this->user);
        }
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UsernameNotFoundException if the user is not found
     *
     */
    public function loadUserByUsername($username)
    {
        return $this->user;
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     * @param UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        return $this->user;
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $class === 'Sulu\Bundle\CoreBundle\Entity\TestUser';
    }
}
