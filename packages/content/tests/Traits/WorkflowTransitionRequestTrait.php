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

namespace Sulu\Content\Tests\Traits;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Admin\ExampleAdmin;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait WorkflowTransitionRequestTrait
{
    use CreateExampleTrait;

    /**
     * Template tagged with the plain `review` workflow: one approval, no validators, no pre-validators.
     */
    private const REVIEW_TEMPLATE = 'example-review-workflow';

    /**
     * Status comes from the workflow config now, so a test asks the same resolver the application does.
     */
    private function resolveRequestStatus(WorkflowTransitionRequest $request): WorkflowTransitionRequestStatusEnum
    {
        /** @var WorkflowTransitionRequestStatusResolverInterface $resolver */
        $resolver = static::getContainer()->get(WorkflowTransitionRequestStatusResolverInterface::class);

        return $resolver->resolve($request);
    }

    private function createExampleAtDraft(string $template = self::REVIEW_TEMPLATE): Example
    {
        $example = static::createExample(
            [
                'en' => [
                    'live' => [
                        'template' => $template,
                        'title' => 'Published Title',
                        'url' => '/published-title',
                    ],
                    'draft' => [
                        'template' => $template,
                        'title' => 'Draft Title',
                        'url' => '/draft-title',
                    ],
                ],
            ],
            ['create_route' => true],
        );
        static::getEntityManager()->flush();

        return $example;
    }

    private function createRequestCreator(string $username = 'request_creator'): User
    {
        $entityManager = static::getEntityManager();

        $contact = new Contact();
        $contact->setFirstName('Request');
        $contact->setLastName('Creator');
        $entityManager->persist($contact);

        $user = new User();
        $user->setUsername($username);
        $user->setPassword('test');
        $user->setSalt('salt');
        $user->setLocale('en');
        $user->setContact($contact);
        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function authenticateAsRequestCreator(string $username = 'request_creator'): User
    {
        $user = $this->createRequestCreator($username);
        static::getContainer()->get('security.token_storage')
            ->setToken(new UsernamePasswordToken($user, 'test', []));

        return $user;
    }

    /**
     * The TestVoter grants every permission for the username "test", so a test that wants the real
     * SecurityChecker to decide has to rename the user first. The password stays "test".
     */
    private function renameTestUserTo(string $username): void
    {
        $user = static::getTestUser();
        $user->setUsername($username);
        static::getEntityManager()->flush();
    }

    private function grantTestUserViewAndEditOnly(): void
    {
        $entityManager = static::getEntityManager();
        $testUser = static::getTestUser();

        $role = new Role();
        $role->setName('view_and_edit_only');
        $role->setSystem('Sulu');
        $entityManager->persist($role);

        // VIEW (64) + EDIT (16) = 80. No LIVE (2), no REVIEW (128).
        $permission = new Permission();
        $permission->setRole($role);
        $permission->setContext(ExampleAdmin::SECURITY_CONTEXT);
        $permission->setPermissions(80);
        $entityManager->persist($permission);
        $role->addPermission($permission);

        $userRole = new UserRole();
        $userRole->setUser($testUser);
        $userRole->setRole($role);
        $userRole->setLocale('["en"]');
        $entityManager->persist($userRole);
        $testUser->addUserRole($userRole);

        $entityManager->flush();
    }
}
