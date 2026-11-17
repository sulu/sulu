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
     * The actions of the core, which the request controller handles itself, so a bundle's action under
     * one of these keys would never be reached.
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
        $action = $this->actions->get($key);

        if (\in_array($key, self::RESERVED_KEYS, true)) {
            throw new \LogicException(\sprintf(
                'The workflow transition request action "%s" uses the key "%s", which belongs to the core. Reserved keys: %s.',
                $action::class,
                $key,
                \implode(', ', self::RESERVED_KEYS),
            ));
        }

        return $action;
    }
}
