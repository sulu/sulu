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

namespace Sulu\Content\Tests\Unit\Content\Application\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\Security\WorkflowTransitionAuthorizer;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[CoversClass(WorkflowTransitionAuthorizer::class)]
class WorkflowTransitionAuthorizerTest extends TestCase
{
    use ProphecyTrait;

    public function testCanPublishWithTheLivePermission(): void
    {
        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission(Argument::any(), PermissionTypes::LIVE)->willReturn(true);
        // The live permission answers on its own, the request is never looked up.
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->shouldNotBeCalled();

        $this->createAuthorizer($securityChecker, $provider)
            ->assertCanPublish(Example::RESOURCE_KEY, '1', 'en');
    }

    public function testCanPublishWithTheEditPermissionAndAnApprovedRequest(): void
    {
        $this->createAuthorizer(
            $this->securityChecker(live: false, edit: true),
            $this->activeRequest($this->approvedRequest()),
        )->assertCanPublish(Example::RESOURCE_KEY, '1', 'en');

        $this->expectNotToPerformAssertions();
    }

    public function testCannotPublishWithTheEditPermissionWhileTheRequestIsPending(): void
    {
        $authorizer = $this->createAuthorizer(
            $this->securityChecker(live: false, edit: true),
            $this->activeRequest(new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default')),
        );

        $this->expectException(AccessDeniedException::class);

        $authorizer->assertCanPublish(Example::RESOURCE_KEY, '1', 'en');
    }

    /**
     * Without a request workflow there is nothing to approve, so the edit permission alone never
     * reaches live.
     */
    public function testCannotPublishWithTheEditPermissionAndNoRequest(): void
    {
        $authorizer = $this->createAuthorizer(
            $this->securityChecker(live: false, edit: true),
            $this->activeRequest(null),
        );

        $this->expectException(AccessDeniedException::class);

        $authorizer->assertCanPublish(Example::RESOURCE_KEY, '1', 'en');
    }

    public function testCannotPublishWithoutTheEditPermission(): void
    {
        $securityChecker = $this->securityChecker(live: false, edit: false);
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->findForContent(Argument::any())->shouldNotBeCalled();

        $authorizer = $this->createAuthorizer($securityChecker, $provider);

        $this->expectException(AccessDeniedException::class);

        $authorizer->assertCanPublish(Example::RESOURCE_KEY, '1', 'en');
    }

    public function testCanRejectWithTheReviewPermission(): void
    {
        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission(Argument::any(), PermissionTypes::REVIEW)->willReturn(true);

        $this->createAuthorizer($securityChecker, $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class))
            ->assertCanReject(Example::RESOURCE_KEY, '1', 'en');

        $this->expectNotToPerformAssertions();
    }

    /**
     * The content API takes `?action=reject` as an ordinary transition, which the security listener
     * maps to EDIT. Without this an editor could reject a request they may not review.
     */
    public function testCannotRejectWithTheEditPermissionAlone(): void
    {
        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission(Argument::any(), PermissionTypes::REVIEW)->willReturn(false);
        $securityChecker->hasPermission(Argument::any(), PermissionTypes::EDIT)->willReturn(true);

        $authorizer = $this->createAuthorizer(
            $securityChecker,
            $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class),
        );

        $this->expectException(AccessDeniedException::class);

        $authorizer->assertCanReject(Example::RESOURCE_KEY, '1', 'en');
    }

    /**
     * A command or a message consumer publishes on the system's behalf: there is no user whose
     * permissions could be checked, so the authorizer steps aside instead of refusing.
     */
    public function testASystemCallWithoutAnAuthenticatedUserIsLetThrough(): void
    {
        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission(Argument::cetera())->shouldNotBeCalled();

        $securityContextResolver = $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class);
        $securityContextResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn(null);

        $authorizer = new WorkflowTransitionAuthorizer(
            $securityContextResolver->reveal(),
            $securityChecker->reveal(),
            $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class)->reveal(),
            $tokenStorage->reveal(),
            $this->statusResolver(),
        );

        $authorizer->assertCanPublish(Example::RESOURCE_KEY, '1', 'en');
    }

    /**
     * Mirrors what the real resolver would answer for these fixtures: the one with an approval on it
     * counts as approved, an untouched request stays pending.
     */
    private function statusResolver(): WorkflowTransitionRequestStatusResolverInterface
    {
        $resolver = $this->prophesize(WorkflowTransitionRequestStatusResolverInterface::class);
        $resolver->resolve(Argument::type(WorkflowTransitionRequest::class))->will(
            static function(array $arguments): WorkflowTransitionRequestStatusEnum {
                $request = $arguments[0];

                return $request instanceof WorkflowTransitionRequest && $request->countUserApprovals() > 0
                    ? WorkflowTransitionRequestStatusEnum::APPROVED
                    : WorkflowTransitionRequestStatusEnum::PENDING;
            },
        );

        return $resolver->reveal();
    }

    private function approvedRequest(): WorkflowTransitionRequest
    {
        $request = new WorkflowTransitionRequest(Example::RESOURCE_KEY, '1', 'en', 'default');
        $reviewer = $this->prophesize(UserInterface::class);
        $reviewer->getId()->willReturn(2);
        $request->addApproval($reviewer->reveal(), WorkflowTransitionRequestDecisionMessage::text('looks good'));

        return $request;
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy<SecurityCheckerInterface>
     */
    private function securityChecker(bool $live, bool $edit)
    {
        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->hasPermission(Argument::any(), PermissionTypes::LIVE)->willReturn($live);
        $securityChecker->hasPermission(Argument::any(), PermissionTypes::EDIT)->willReturn($edit);

        return $securityChecker;
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy<ActiveWorkflowTransitionRequestProviderInterface>
     */
    private function activeRequest(?WorkflowTransitionRequest $request)
    {
        $provider = $this->prophesize(ActiveWorkflowTransitionRequestProviderInterface::class);
        $provider->find(Example::RESOURCE_KEY, '1', 'en')->willReturn($request);

        return $provider;
    }

    /**
     * @param \Prophecy\Prophecy\ObjectProphecy<SecurityCheckerInterface> $securityChecker
     * @param \Prophecy\Prophecy\ObjectProphecy<ActiveWorkflowTransitionRequestProviderInterface> $provider
     */
    private function createAuthorizer($securityChecker, $provider): WorkflowTransitionAuthorizer
    {
        $securityContextResolver = $this->prophesize(WorkflowTransitionRequestSecurityContextResolverInterface::class);
        $securityContextResolver->resolve(Example::RESOURCE_KEY, '1', 'en')
            ->willReturn(new SecurityCondition('sulu.example', 'en'));

        return new WorkflowTransitionAuthorizer(
            $securityContextResolver->reveal(),
            $securityChecker->reveal(),
            $provider->reveal(),
            $this->authenticatedTokenStorage(),
            $this->statusResolver(),
        );
    }

    private function authenticatedTokenStorage(): TokenStorageInterface
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($this->prophesize(UserInterface::class)->reveal());

        $tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $tokenStorage->getToken()->willReturn($token->reveal());

        return $tokenStorage->reveal();
    }
}
