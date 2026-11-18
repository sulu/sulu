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

namespace Sulu\Content\Application\WorkflowTransitionRequest\Action;

use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;

/**
 * An action a bundle adds to the footer of the review overlay, next to approving, rejecting and
 * publishing. It is autoconfigured with the tag `sulu_content.workflow_transition_request_action`.
 *
 * The admin lists the actions that are available for a request and that the current user may take.
 * A click sends the action's key to the request, and {@see execute()} runs it.
 *
 * @experimental This is an experimental feature and may change in future releases.
 */
interface WorkflowTransitionRequestActionInterface
{
    /**
     * Unique name that identifies the action in the admin API. Static because the registry indexes
     * the services by it and stays lazy. The keys `approve`, `reject`, `retry`, `cancel` and `publish`
     * belong to the core and are refused.
     */
    public static function getKey(): string;

    /**
     * Translation key of the button label in the admin translation domain.
     */
    public function getLabel(): string;

    /**
     * The values for the placeholders of the label, for example the date a request names. The admin passes
     * them to the translation of {@see getLabel()}. Return an empty array when the label has none.
     *
     * @return array<string, string>
     */
    public function getLabelParameters(WorkflowTransitionRequest $request): array;

    /**
     * The permission, one of {@see \Sulu\Component\Security\Authorization\PermissionTypes}, that the user
     * needs on the content of the request to see and to take the action.
     */
    public function getPermission(): string;

    /**
     * Whether the action fits the request in its current state, for example because the form data it
     * works on is there. Only open requests are asked.
     */
    public function isAvailable(WorkflowTransitionRequest $request): bool;

    /**
     * A message for the banner of the form while the request is open, which replaces the one of the core, for example
     * to tell that the publish is scheduled. The message is a translation key, the parameters fill its placeholders.
     * The first action that answers wins, and it is asked whether the action is available or not.
     *
     * @return array{message: string, parameters: array<string, string>}|null
     */
    public function getStatusMessage(WorkflowTransitionRequest $request): ?array;

    /**
     * Whether the content may only be published through this action while the request is open, for example
     * because the request names a date. The admin then hides the plain publish button, and the content workflow
     * refuses a publish by a user. A publish without a user, like the one of a scheduled task, stays possible.
     */
    public function replacesPublish(WorkflowTransitionRequest $request): bool;

    /**
     * Runs inside the request that sent it and is flushed with it, so it can change the request and
     * the entities of the application. Throw an exception to refuse.
     *
     * @param array<string, mixed> $data the body of the request that sent the action
     */
    public function execute(WorkflowTransitionRequest $request, array $data): void;
}
