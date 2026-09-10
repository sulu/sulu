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

namespace Sulu\Content\UserInterface\Controller\Admin;

use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\Message\ApproveWorkflowTransitionRequestMessage;
use Sulu\Content\Application\Message\RejectWorkflowTransitionRequestMessage;
use Sulu\Content\Application\Message\RetryWorkflowTransitionRequestValidationMessage;
use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\WorkflowTransitionRequestViewFactoryInterface;
use Sulu\Content\Domain\Exception\UnresolvableSecurityContextException;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestNotFoundException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal there should be no need to extend this class
 */
final class WorkflowTransitionRequestController extends AbstractRestController
{
    use HandleTrait;

    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
        private readonly SecurityCheckerInterface $securityChecker,
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $securityContextResolver,
        private readonly WorkflowTransitionRequestViewFactoryInterface $viewFactory,
        private readonly WorkflowTransitionRequestStatusResolverInterface $statusResolver,
        MessageBusInterface $messageBus,
        ViewHandlerInterface $viewHandler,
        ?TokenStorageInterface $tokenStorage = null,
    ) {
        $this->messageBus = $messageBus;
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $resourceKey = $this->getRequiredQueryParameter($request, 'resourceKey');
        $resourceId = $this->getRequiredQueryParameter($request, 'resourceId');
        $locale = $this->getRequiredQueryParameter($request, 'locale');

        try {
            $securityCondition = $this->securityContextResolver->resolve($resourceKey, $resourceId, $locale);
        } catch (UnresolvableSecurityContextException $exception) {
            // The resource key comes from the query here, so an unknown one is the caller's mistake
            // rather than a broken installation.
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $this->securityChecker->checkPermission($securityCondition, PermissionTypes::VIEW);

        $filters = ['resourceKey' => $resourceKey, 'resourceId' => $resourceId, 'locale' => $locale];
        $limit = \max(1, $request->query->getInt('limit', 10));
        $page = \max(1, $request->query->getInt('page', 1));

        $listRepresentation = new PaginatedRepresentation(
            \array_map(
                fn (WorkflowTransitionRequest $workflowTransitionRequest) => [
                    'id' => $workflowTransitionRequest->getId(),
                    'requester' => $workflowTransitionRequest->getCreator()?->getFullName(),
                    'status' => $this->statusResolver->resolve($workflowTransitionRequest)->value,
                ],
                $this->workflowTransitionRequestRepository->findBy($filters, $limit, ($page - 1) * $limit),
            ),
            'workflow_transition_requests',
            $page,
            $limit,
            $this->workflowTransitionRequestRepository->countBy($filters),
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(string $id): Response
    {
        try {
            $workflowTransitionRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $id]);
        } catch (WorkflowTransitionRequestNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        $this->securityChecker->checkPermission(
            $this->securityCondition($workflowTransitionRequest),
            PermissionTypes::VIEW,
        );

        return $this->handleView($this->view($this->viewFactory->build($workflowTransitionRequest)));
    }

    public function postTriggerAction(string $id, Request $request): Response
    {
        $action = $request->query->get('action');
        if (null === $action || '' === $action) {
            throw new BadRequestHttpException('The "action" query parameter is required.');
        }

        try {
            $workflowTransitionRequest = $this->workflowTransitionRequestRepository->getOneBy(['id' => $id]);
        } catch (WorkflowTransitionRequestNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        // Approving and rejecting are a reviewer's verdict. Re-running a check is not a verdict on
        // anything: it is how whoever is fixing the content clears a check that failed, so it takes
        // the same permission as editing.
        $this->securityChecker->checkPermission(
            $this->securityCondition($workflowTransitionRequest),
            'retry' === $action ? PermissionTypes::EDIT : PermissionTypes::REVIEW,
        );

        $comment = $this->getComment($request);

        // Cancelling runs as the `cancel_review` content transition, because it moves the content's workflow place too.
        $message = match ($action) {
            'approve' => new ApproveWorkflowTransitionRequestMessage($id, $comment),
            'reject' => new RejectWorkflowTransitionRequestMessage(
                $id,
                $comment ?? throw new BadRequestHttpException('A rejection requires a "comment".'),
            ),
            'retry' => new RetryWorkflowTransitionRequestValidationMessage(
                $id,
                $this->getValidatorKey($request, $workflowTransitionRequest),
            ),
            default => throw new BadRequestHttpException(\sprintf('Unrecognized action "%s".', $action)),
        };

        /** @var WorkflowTransitionRequest $workflowTransitionRequest */
        $workflowTransitionRequest = $this->handle(new Envelope($message, [new EnableFlushStamp()]));

        return $this->handleView($this->view($this->viewFactory->build($workflowTransitionRequest)));
    }

    private function securityCondition(WorkflowTransitionRequest $workflowTransitionRequest): SecurityCondition
    {
        return $this->securityContextResolver->resolve(
            $workflowTransitionRequest->getResourceKey(),
            $workflowTransitionRequest->getResourceId(),
            $workflowTransitionRequest->getLocale(),
        );
    }

    private function getValidatorKey(Request $request, WorkflowTransitionRequest $workflowTransitionRequest): string
    {
        $validatorKey = $request->query->get('validator');
        if (null === $validatorKey || '' === $validatorKey) {
            throw new BadRequestHttpException('The "validator" query parameter is required.');
        }

        $validatorKey = (string) $validatorKey;

        if (null === $workflowTransitionRequest->getValidatorDecision($validatorKey)) {
            throw new BadRequestHttpException(\sprintf('The request has no check "%s" to retry.', $validatorKey));
        }

        return $validatorKey;
    }

    private function getRequiredQueryParameter(Request $request, string $name): string
    {
        $value = $request->query->get($name);
        if (null === $value || '' === $value) {
            throw new BadRequestHttpException(\sprintf('The "%s" query parameter is required.', $name));
        }

        return (string) $value;
    }

    private function getComment(Request $request): ?string
    {
        /** @var string|null $comment */
        $comment = $request->request->get('comment');

        return null === $comment || '' === \trim($comment) ? null : $comment;
    }
}
