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

namespace Sulu\Webspace\Domain\Event;

use Sulu\Webspace\Domain\Model\WebspaceSettingInterface;

class WebspaceSettingWorkflowTransitionRequestEvent extends AbstractWebspaceSettingEvent
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function __construct(
        WebspaceSettingInterface $webspaceSetting,
        private string $action,
        string $locale,
        private array $context,
    ) {
        parent::__construct($webspaceSetting, $locale);
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getEventType(): string
    {
        return 'workflow_transition_request.' . $this->action;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getEventContext(): array
    {
        return $this->context;
    }
}
