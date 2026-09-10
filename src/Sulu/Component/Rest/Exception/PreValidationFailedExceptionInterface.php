<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

/**
 * Carries the outcome of every check that ran, passed ones included, so the admin can list them the
 * way it lists reviewers instead of showing a single run-on sentence. Messages are translation keys
 * with their parameters, because only the serializer knows the request's locale.
 */
interface PreValidationFailedExceptionInterface
{
    /**
     * Ordered as configured.
     *
     * @return list<array{key: string, passed: bool, failures: list<array{messageKey: string, messageParameters: array<string, float|int|string>}>}>
     */
    public function getPreValidationResults(): array;
}
