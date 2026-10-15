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

namespace Sulu\Content\Domain\Repository;

use Sulu\Content\Domain\Exception\WorkflowTransitionRequestNotFoundException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecision;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;

/**
 * @internal
 */
interface WorkflowTransitionRequestRepositoryInterface
{
    /**
     * @param array{
     *     id?: string,
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     *
     * @throws WorkflowTransitionRequestNotFoundException
     */
    public function getOneBy(array $filters): WorkflowTransitionRequest;

    /**
     * @param array{
     *     id?: string,
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     */
    public function findOneBy(array $filters): ?WorkflowTransitionRequest;

    /**
     * @param array{
     *     id?: string,
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     */
    public function countBy(array $filters = []): int;

    /**
     * Requests for the admin list, newest first. The list shape is the caller's business: the status
     * is derived from the workflow config, which a repository has no reason to read.
     *
     * @param array{
     *     id?: string,
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     *
     * @return list<WorkflowTransitionRequest>
     */
    public function findBy(array $filters, ?int $limit = null, ?int $offset = null): array;

    public function add(WorkflowTransitionRequest $workflowTransitionRequest): void;

    /**
     * Claims a pending check row, writes its verdict in one statement (the first writer wins) and counts
     * the request's checks by status. Both happen under a lock on the request, so of several checks
     * settling on different workers the last one counts all the others as settled.
     *
     * @param list<WorkflowTransitionRequestDecisionMessage> $messages
     *
     * @return array{approved: int, rejected: int, pending: int}|null null when another writer had settled the row already or the request is no longer open
     */
    public function settleDecision(
        WorkflowTransitionRequestDecision $decision,
        WorkflowTransitionRequestDecisionStatusEnum $status,
        array $messages,
    ): ?array;
}
