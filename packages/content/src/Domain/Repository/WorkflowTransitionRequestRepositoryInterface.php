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
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestCheck;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestCheckStatusEnum;

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
     * Rows for the admin list, newest request first.
     *
     * @param array{
     *     id?: string,
     *     resourceKey?: string,
     *     resourceId?: string,
     *     locale?: string,
     *     active?: bool,
     * } $filters
     *
     * @return list<array{id: string, requester: string|null, status: string}>
     */
    public function findFlatBy(array $filters, ?int $limit = null, ?int $offset = null): array;

    public function add(WorkflowTransitionRequest $workflowTransitionRequest): void;

    /**
     * Claims a pending check row and writes its verdict in one statement; the first writer wins.
     */
    public function settleCheck(
        WorkflowTransitionRequestCheck $check,
        WorkflowTransitionRequestCheckStatusEnum $status,
        ?string $comment,
    ): void;
}
