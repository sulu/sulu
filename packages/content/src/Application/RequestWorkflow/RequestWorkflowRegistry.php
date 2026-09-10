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

namespace Sulu\Content\Application\RequestWorkflow;

use Sulu\Content\Application\RequestWorkflow\PreValidator\RequestWorkflowPreValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Domain\Exception\UnknownRequestWorkflowException;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Builds the {@see RequestWorkflow} objects from the `sulu_content.request_workflows` config tree.
 *
 * @internal
 */
final class RequestWorkflowRegistry implements RequestWorkflowRegistryInterface
{
    /**
     * @var array<string, RequestWorkflow>
     */
    private readonly array $workflows;

    /**
     * @param array<string, array{resources?: list<string>, validators?: array<string, array<string, mixed>|null>, pre_validators?: array<string, array<string, mixed>|null>, required_user_approvals?: int}> $config
     * @param ServiceLocator<RequestWorkflowValidatorInterface> $validators
     * @param ServiceLocator<RequestWorkflowPreValidatorInterface> $preValidators
     */
    public function __construct(
        array $config,
        private readonly ServiceLocator $validators,
        private readonly ServiceLocator $preValidators,
    ) {
        $workflows = [];
        foreach ($config as $name => $workflowConfig) {
            if (RequestWorkflow::NONE_NAME === $name) {
                throw new \LogicException(\sprintf(
                    'Workflow name "%s" is reserved: a template tagged with it opts out of review.',
                    RequestWorkflow::NONE_NAME,
                ));
            }

            if (RequestWorkflow::DEFAULT_NAME !== $name && [] !== ($workflowConfig['resources'] ?? [])) {
                throw new \LogicException(\sprintf(
                    'Workflow "%s" declares `resources`, which is only supported on the `%s` workflow; a named workflow is selected by its template tag.',
                    $name,
                    RequestWorkflow::DEFAULT_NAME,
                ));
            }

            $workflows[$name] = $this->buildWorkflow($name, $workflowConfig);
        }

        $this->workflows = $workflows;
    }

    public function get(string $name): RequestWorkflow
    {
        if (!isset($this->workflows[$name])) {
            throw new UnknownRequestWorkflowException($name);
        }

        return $this->workflows[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->workflows[$name]);
    }

    /**
     * @param array{resources?: list<string>, validators?: array<string, array<string, mixed>|null>, pre_validators?: array<string, array<string, mixed>|null>, required_user_approvals?: int} $config
     */
    private function buildWorkflow(string $name, array $config): RequestWorkflow
    {
        $validators = [];
        foreach ($config['validators'] ?? [] as $key => $entryConfig) {
            $entryConfig = \is_array($entryConfig) ? $entryConfig : [];
            // `required` is the workflow's word about the check, not the check's own config, so it
            // is lifted out before the rest is handed over untouched.
            $required = (bool) ($entryConfig['required'] ?? false);
            unset($entryConfig['required']);

            $validators[$key] = [
                'validator' => $this->resolveService($this->validators, 'validator', $name, $key),
                'config' => $entryConfig,
                'required' => $required,
            ];
        }

        $preValidators = [];
        foreach ($config['pre_validators'] ?? [] as $key => $entryConfig) {
            $preValidators[$key] = [
                'pre_validator' => $this->resolveService($this->preValidators, 'pre_validator', $name, $key),
                'config' => \is_array($entryConfig) ? $entryConfig : [],
            ];
        }

        return new RequestWorkflow(
            $name,
            $validators,
            $config['required_user_approvals'] ?? 1,
            $config['resources'] ?? [],
            $preValidators,
        );
    }

    /**
     * @template T of object
     *
     * @param ServiceLocator<T> $locator
     *
     * @return T
     */
    private function resolveService(ServiceLocator $locator, string $type, string $workflowName, string $key): object
    {
        if (!$locator->has($key)) {
            $known = \array_keys($locator->getProvidedServices());

            throw new \LogicException(\sprintf(
                'Workflow "%s" references unknown %s "%s". Known %ss: %s',
                $workflowName,
                $type,
                $key,
                $type,
                [] === $known ? '(none registered)' : \implode(', ', $known),
            ));
        }

        return $locator->get($key);
    }
}
