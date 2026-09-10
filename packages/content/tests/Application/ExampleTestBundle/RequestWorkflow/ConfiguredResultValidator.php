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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\RequestWorkflow;

use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationContext;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationDecision;

/**
 * Test validator whose verdict comes from its workflow config, so a test can set up an approving, a
 * rejecting or a crashing check without a real one.
 */
final class ConfiguredResultValidator implements RequestWorkflowValidatorInterface
{
    public static function getKey(): string
    {
        return 'test_configured_result';
    }

    public function check(ValidationContext $context): ValidationDecision
    {
        /** @var array{result?: string} $config */
        $config = $context->validatorConfig;

        return match ($config['result'] ?? 'approve') {
            'reject' => ValidationDecision::reject(
                \sprintf('The configured check rejected example %s.', $context->request->getResourceId()),
            ),
            'throw' => throw new \RuntimeException(
                \sprintf('The configured check crashed on example %s.', $context->request->getResourceId()),
            ),
            default => ValidationDecision::approve(),
        };
    }
}
