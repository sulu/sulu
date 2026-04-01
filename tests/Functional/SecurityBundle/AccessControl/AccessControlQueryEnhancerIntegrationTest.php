<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\AccessControl;

use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * Functional test for AccessControlQueryEnhancer using profiler datacollector to verify actual SQL.
 *
 * This test validates that users with multiple roles can access entities when ANY role grants permission (OR logic).
 */
class AccessControlQueryEnhancerIntegrationTest extends SuluTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->getEntityManager();
        $this->purgeDatabase();
    }

    /**
     * Test that a user with multiple roles can access a collection when ANY role grants permission.
     *
     * Scenario:
     * - Create Collection A with AccessControl restricting to Admin role (VIEW permission)
     * - Create user with both Admin role (has VIEW) and User role (no permission)
     * - Expected: User can access Collection A because Admin role grants permission (OR logic)
     * - Verify actual SQL uses EXISTS clause with role.id IN (:roleIds) condition
     */
    public function testUserWithMultipleRolesCanAccessWhenAnyRoleGrantsPermission(): void
    {
        // Create roles
        $adminRole = $this->createRole('Admin');
        $userRole = $this->createRole('User');

        // Create collection type
        $collectionType = new CollectionType();
        $collectionType->setName('collection');
        $collectionType->setKey('collection');
        $this->entityManager->persist($collectionType);
        $this->entityManager->flush();

        // Create restricted collection
        $collection = new Collection();
        $collection->setType($collectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Test Collection');
        $collectionMeta->setLocale('en');
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        // Set permissions: Admin role has VIEW, User role has no permissions
        $this->getAccessControlManager()->setPermissions(
            Collection::class,
            (string) $collection->getId(),
            [
                $adminRole->getId() => ['view' => true, 'edit' => false, 'delete' => false],
                $userRole->getId() => ['view' => false, 'edit' => false, 'delete' => false],
            ]
        );

        // Create user with both roles
        $user = $this->createUser('testuser', [$adminRole, $userRole]);

        // Enable profiler
        $this->client->enableProfiler();

        // Login as the test user
        $this->client->loginUser($user);

        // Request collections list
        $this->client->request('GET', '/admin/api/collections?locale=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        // Verify collection is accessible
        $collections = $this->getCollectionsFromResponse();
        $collectionIds = \array_column($collections, 'id');
        $this->assertContains($collection->getId(), $collectionIds, 'User should be able to access collection through Admin role');

        // Verify SQL uses EXISTS with role.id IN condition
        $queries = $this->getDoctrineQueries();

        // Find query that checks AccessControl
        $accessControlQuery = null;
        $accessControlParams = null;

        foreach ($queries['default'] as $query) {
            if (\str_contains($query['sql'], 'se_access_controls') && \str_contains($query['sql'], 'EXISTS')) {
                $accessControlQuery = $query['sql'];
                $accessControlParams = $query['params'];

                break;
            }
        }

        $this->assertNotNull($accessControlQuery, 'Query should contain AccessControl check with EXISTS');
        $this->assertIsArray($accessControlParams, 'Query should have parameters');

        // Verify EXISTS subquery structure
        $this->assertStringContainsString('EXISTS', $accessControlQuery, 'Should use EXISTS clause');
        $this->assertStringContainsString('se_access_controls', $accessControlQuery, 'Should query AccessControl table');
        $this->assertStringContainsString('INNER JOIN', $accessControlQuery, 'Should join with roles table');
        $this->assertStringContainsString('IN (?)', $accessControlQuery, 'Should check role IDs with IN clause');
        $this->assertStringContainsString('BIT_AND', $accessControlQuery, 'Should use BIT_AND for permission check');

        // Verify the query parameters contain both role IDs
        $roleIdsParam = null;

        foreach ($accessControlParams as $param) {
            if (\is_array($param) && \in_array($adminRole->getId(), $param, true)) {
                $roleIdsParam = $param;

                break;
            }
        }

        $this->assertNotNull($roleIdsParam, 'Query parameters should contain role IDs');
        $this->assertContains($adminRole->getId(), $roleIdsParam, 'Should include Admin role ID');
        $this->assertContains($userRole->getId(), $roleIdsParam, 'Should include User role ID');
    }

    /**
     * Test that a user with multiple roles CANNOT access when NO role grants permission.
     *
     * Scenario:
     * - Create Collection B with AccessControl restricting to SuperAdmin role (VIEW permission)
     * - Create user with Admin and User roles (neither has permission)
     * - Expected: User CANNOT access Collection B (OR logic: no role grants access)
     */
    public function testUserWithMultipleRolesCannotAccessWhenNoRoleGrantsPermission(): void
    {
        // Create roles
        $adminRole = $this->createRole('Admin');
        $userRole = $this->createRole('User');
        $superAdminRole = $this->createRole('SuperAdmin');

        // Create collection type
        $collectionType = new CollectionType();
        $collectionType->setName('collection');
        $collectionType->setKey('collection');
        $this->entityManager->persist($collectionType);
        $this->entityManager->flush();

        // Create restricted collection
        $collection = new Collection();
        $collection->setType($collectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Super Admin Only Collection');
        $collectionMeta->setLocale('en');
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        // Set permissions: Only SuperAdmin role has VIEW
        $this->getAccessControlManager()->setPermissions(
            Collection::class,
            (string) $collection->getId(),
            [
                $superAdminRole->getId() => ['view' => true, 'edit' => true, 'delete' => true],
                $adminRole->getId() => ['view' => false, 'edit' => false, 'delete' => false],
                $userRole->getId() => ['view' => false, 'edit' => false, 'delete' => false],
            ]
        );

        // Create user with Admin and User roles (not SuperAdmin)
        $user = $this->createUser('testuser', [$adminRole, $userRole]);

        // Login as the test user
        $this->client->loginUser($user);

        // Request collections list
        $this->client->request('GET', '/admin/api/collections?locale=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        // Verify collection is NOT accessible
        $collections = $this->getCollectionsFromResponse();
        $collectionIds = \array_column($collections, 'id');
        $this->assertNotContains(
            $collection->getId(),
            $collectionIds,
            'User should NOT be able to access collection as no role grants permission'
        );
    }

    /**
     * Test SQL generation for single role scenario to ensure backwards compatibility.
     */
    public function testUserWithSingleRoleGeneratesCorrectSQL(): void
    {
        // Create role
        $adminRole = $this->createRole('Admin');

        // Create collection type
        $collectionType = new CollectionType();
        $collectionType->setName('collection');
        $collectionType->setKey('collection');
        $this->entityManager->persist($collectionType);
        $this->entityManager->flush();

        // Create restricted collection
        $collection = new Collection();
        $collection->setType($collectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Single Role Collection');
        $collectionMeta->setLocale('en');
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        // Set permissions: Admin role has VIEW
        $this->getAccessControlManager()->setPermissions(
            Collection::class,
            (string) $collection->getId(),
            [
                $adminRole->getId() => ['view' => true, 'edit' => false, 'delete' => false],
            ]
        );

        // Create user with single role
        $user = $this->createUser('testuser', [$adminRole]);

        // Enable profiler
        $this->client->enableProfiler();

        // Login as the test user
        $this->client->loginUser($user);

        // Request collections list
        $this->client->request('GET', '/admin/api/collections?locale=en');

        $queries = $this->getDoctrineQueries();

        // Find AccessControl query
        $accessControlQuery = null;

        foreach ($queries['default'] as $query) {
            if (\str_contains($query['sql'], 'se_access_controls') && \str_contains($query['sql'], 'EXISTS')) {
                $accessControlQuery = $query['sql'];

                break;
            }
        }

        $this->assertNotNull($accessControlQuery, 'Should generate AccessControl query');
        $this->assertStringContainsString('EXISTS', $accessControlQuery, 'Should use EXISTS for single role too');
    }

    public function testUserWithoutRolesCanAccessUnrestrictedCollection(): void
    {
        $collectionType = new CollectionType();
        $collectionType->setName('collection');
        $collectionType->setKey('collection');
        $this->entityManager->persist($collectionType);
        $this->entityManager->flush();

        $collection = new Collection();
        $collection->setType($collectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Unrestricted Collection');
        $collectionMeta->setLocale('en');
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $user = $this->createUser('testuser', []);

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/api/collections?locale=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $collections = $this->getCollectionsFromResponse();
        $collectionIds = \array_column($collections, 'id');
        $this->assertContains($collection->getId(), $collectionIds);
    }

    public function testRestrictedCollectionInOtherSystemDoesNotHideEntity(): void
    {
        $suluRole = $this->createRole('Admin');
        $websiteRole = $this->createRole('Website Admin', 'Website');

        $collectionType = new CollectionType();
        $collectionType->setName('collection');
        $collectionType->setKey('collection');
        $this->entityManager->persist($collectionType);
        $this->entityManager->flush();

        $collection = new Collection();
        $collection->setType($collectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle('Website Restricted Collection');
        $collectionMeta->setLocale('en');
        $collectionMeta->setCollection($collection);
        $collection->addMeta($collectionMeta);

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $this->getAccessControlManager()->setPermissions(
            Collection::class,
            (string) $collection->getId(),
            [
                $websiteRole->getId() => ['view' => true, 'edit' => false, 'delete' => false],
            ]
        );

        $user = $this->createUser('testuser', [$suluRole]);

        $this->client->loginUser($user);
        $this->client->request('GET', '/admin/api/collections?locale=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);

        $collections = $this->getCollectionsFromResponse();
        $collectionIds = \array_column($collections, 'id');
        $this->assertContains($collection->getId(), $collectionIds);
    }

    private function getAccessControlManager(): AccessControlManagerInterface
    {
        /** @var AccessControlManagerInterface $accessControlManager */
        $accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');

        return $accessControlManager;
    }

    /**
     * @return array{default: list<array{sql: string, params: list<mixed>}>}
     */
    private function getDoctrineQueries(): array
    {
        /** @var Profile|null $profile */
        $profile = $this->client->getProfile();
        self::assertInstanceOf(Profile::class, $profile, 'Profiler should be enabled');

        $collector = $profile->getCollector('db');
        self::assertInstanceOf(DoctrineDataCollector::class, $collector);

        /** @var array{default: list<array{sql: string, params: list<mixed>}>} $queries */
        $queries = $collector->getQueries();

        return $queries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getCollectionsFromResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        /** @var array{_embedded: array{collections: list<array<string, mixed>>}} $data */
        $data = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return $data['_embedded']['collections'];
    }

    private function createRole(string $name, string $system = 'Sulu'): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return $role;
    }

    /**
     * @param list<RoleInterface> $roles
     */
    private function createUser(string $username, array $roles): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword('password');
        $user->setLocale('en');
        $user->setSalt('salt');

        foreach ($roles as $role) {
            $userRole = new UserRole();
            $userRole->setRole($role);
            $userRole->setUser($user);
            $userRole->setLocale('["en"]');
            $user->addUserRole($userRole);

            $this->entityManager->persist($userRole);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
