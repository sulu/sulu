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

namespace Sulu\Page\Domain\Event;

use Sulu\Page\Domain\Model\WebspaceSettingInterface;

class WebspaceSettingWorkflowTransitionAppliedEvent extends AbstractWebspaceSettingEvent
{
    public function __construct(
        WebspaceSettingInterface $webspaceSetting,
        private string $workflowTransitionName,
        string $locale,
    ) {
        parent::__construct($webspaceSetting, $locale);
    }

    public function getWorkflowTransitionName(): string
    {
        return $this->workflowTransitionName;
    }

    public function getEventType(): string
    {
        return 'workflow_transition.' . $this->workflowTransitionName;
    }
}
