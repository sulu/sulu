<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Behat\BaseContext;

/**
 * Behat context class for the SecurityBundle.
 */
class SecurityContext extends BaseContext implements SnippetAcceptingContext
{
    const ADMIN_USERNAME = 'admin';
    const ADMIN_PASSWORD = 'admin';

    /**
     * @Given the user :username exists with password :password
     */
    public function theUserExistsWithPassword($username, $password)
    {
        $this->createUser($username, $password, 'en', false);
    }

    /**
     * @Given the user :username exists with password :password and locale :locale
     */
    public function theUserExistsWithPasswordAndLocale($username, $password, $locale)
    {
        $this->createUser($username, $password, $locale, true);
    }

    /**
     * @Given the following users exist:
     */
    public function theFollowingUsersExist(TableNode $users)
    {
        $this->getOrCreateRole('User', 'Sulu');
        $users = $users->getColumnsHash();

        foreach ($users as $user) {
            $this->execCommand('sulu:security:user:create', [
                'username' => $user['username'],
                'firstName' => $user['firstName'],
                'lastName' => $user['lastName'],
                'email' => $user['email'],
                'locale' => $user['locale'],
                'password' => $user['password'],
                'role' => 'admin',
            ]);
        }
    }

    /**
     * @Given the following roles exist:
     */
    public function theFollowingRolesExist(TableNode $roles)
    {
        $roleData = $roles->getColumnsHash();

        foreach ($roleData as $roleDatum) {
            $this->getOrCreateRole($roleDatum['name'], $roleDatum['system']);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @Then the role :name should not exist
     */
    public function theRoleShouldNotExist($name)
    {
        $role = $this->getEntityManager()
            ->getRepository('SuluSecurityBundle:Role')->findOneBy([
                'name' => $name,
            ]);

        if ($role) {
            throw new \Exception(sprintf('Role with name "%s" should NOT exist', $name));
        }
    }

    /**
     * @Then the role :name should exist
     */
    public function theRoleShouldExist($name)
    {
        $role = $this->getEntityManager()
            ->getRepository('SuluSecurityBundle:Role')->findOneBy([
                'name' => $name,
            ]);

        if (!$role) {
            throw new \Exception(sprintf('Role with name "%s" should exist', $name));
        }
    }

    /**
     * @Given the not enabled user :username exists with password :password
     */
    public function theNotEnabledUserExistsWithPassword($username, $password)
    {
        $this->getOrCreateRole('User', 'Sulu');
        $this->execCommand('sulu:security:user:create', [
            'username' => $username,
            'firstName' => 'Adam',
            'lastName' => 'Ministrator',
            'email' => $username . '@example.com',
            'locale' => 'en',
            'password' => $password,
            'role' => 'User',
        ]);

        $user = $this->getEntityManager()
            ->getRepository('SuluSecurityBundle:User')->findOneBy([
                'username' => $username,
            ]);
        $user->setEnabled(false);

        $this->getEntityManager()->flush();
    }

    /**
     * @Given I am logged in as an administrator
     */
    public function iAmLoggedInAsAnAdministrator()
    {
        $this->logInAsAdministrator();
    }

    /**
     * @Given I am logged in as an administrator with locale :locale
     *
     * @param string $locale
     */
    public function iAmLoggedInAsAnAdministratorWithLocale($locale)
    {
        $this->logInAsAdministrator($locale);
    }

    /**
     * @Given I am logged in as an administrator with default locale
     */
    public function iAmLoggedInAsAnAdministratorWithDefaultLocale()
    {
        $locale = $this->getContainer()->getParameter('locale');
        $this->logInAsAdministrator($locale);
    }

    /**
     * @Given I am editing the permission of a user with username :username
     */
    public function iAmEditingThePermissionsOfAUser($username)
    {
        /** @var User $user */
        $user = $this->getEntityManager()
            ->getRepository('SuluSecurityBundle:User')->findOneBy(
                ['username' => $username]
            );

        $this->visitPath('/admin/#contacts/contacts/edit:' . $user->getContact()->getId() . '/permissions');
        $this->getSession()->wait(5000, '$("#permissions-grid").length');
    }

    private function getOrCreateRole($name, $system)
    {
        $role = $this->getEntityManager()
            ->getRepository('Sulu\Bundle\SecurityBundle\Entity\Role')
            ->findOneByName($name);

        if ($role) {
            return $role;
        }

        $this->execCommand(
            'sulu:security:role:create',
            [
                'name' => $name,
                'system' => 'Sulu',
            ]
        );

        return $role;
    }

    /**
     * Creates user with given credentials.
     *
     * @param string $username
     * @param string $password
     * @param string $locale
     * @param bool $checkIfUserExists
     *
     * @throws \Exception
     */
    private function createUser($username, $password, $locale, $checkIfUserExists)
    {
        if ($checkIfUserExists) {
            $user = $this->getEntityManager()
                ->getRepository('Sulu\Bundle\SecurityBundle\Entity\User')
                ->findOneByUsername($username);

            if ($user) {
                // User exists already, we don't need to create it again.
                return;
            }
        }

        $this->getOrCreateRole('User', 'Sulu');
        $this->execCommand('sulu:security:user:create', [
            'username' => $username,
            'firstName' => 'Adam',
            'lastName' => 'Ministrator',
            'email' => $username . '@example.com',
            'locale' => $locale,
            'password' => $password,
            'role' => 'User',
        ]);
    }

    /**
     * Login as administrator with given locale.
     *
     * @param string $locale
     */
    private function logInAsAdministrator($locale = null)
    {
        if ($locale) {
            $this->theUserExistsWithPasswordAndLocale(self::ADMIN_USERNAME, self::ADMIN_PASSWORD, $locale);
        } else {
            $this->theUserExistsWithPassword(self::ADMIN_USERNAME, self::ADMIN_PASSWORD);
        }

        $this->visitPath('/admin');
        $page = $this->getSession()->getPage();
        $this->waitForSelector('#username');
        $this->fillSelector('#username', self::ADMIN_USERNAME);
        $this->fillSelector('#password', self::ADMIN_PASSWORD);
        $loginButton = $page->findById('login-button');

        if (!$loginButton) {
            throw new \InvalidArgumentException(
                'Could not find submit button on login page'
            );
        }

        $loginButton->click();
        $this->getSession()->wait(5000, "document.querySelector('.navigation')");
    }
}
