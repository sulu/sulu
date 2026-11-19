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

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final class WorkflowTransitionRequestActionRegistry implements WorkflowTransitionRequestActionRegistryInterface
{
    /**
     * The five keys are reserved for the core. The request controller handles approve, reject and retry,
     * and cancel and publish run as transitions of the content, so the controller answers 400 to them.
     * An action of a bundle under one of these keys would never be reached, and the compiler pass
     * {@see \Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ValidateRequestWorkflowsPass} refuses it.
     */
    public const RESERVED_KEYS = ['approve', 'reject', 'retry', 'cancel', 'publish'];

    /**
     * @param ServiceLocator<WorkflowTransitionRequestActionInterface> $actions
     */
    public function __construct(
        private readonly ServiceLocator $actions,
    ) {
    }

    public function all(): array
    {
        $actions = [];

        foreach (\array_keys($this->actions->getProvidedServices()) as $key) {
            $actions[] = $this->load((string) $key);
        }

        return $actions;
    }

    public function get(string $key): ?WorkflowTransitionRequestActionInterface
    {
        if (!$this->actions->has($key)) {
            return null;
        }

        return $this->load($key);
    }

    private function load(string $key): WorkflowTransitionRequestActionInterface
    {
        return $this->actions->get($key);
    }
}
