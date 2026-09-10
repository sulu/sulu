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

namespace Sulu\Content\Tests\Unit\Content\Application\RequestWorkflow;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;

#[CoversClass(RequestWorkflow::class)]
class RequestWorkflowTest extends TestCase
{
    public function testGetRequiredApprovalCountReadsConfiguredValue(): void
    {
        $workflow = new RequestWorkflow('default', [], 3);

        $this->assertSame(3, $workflow->getRequiredHumanApprovalCount());
    }

    public function testGetRequiredApprovalCountDefaultsToOne(): void
    {
        $workflow = new RequestWorkflow('default', [], 1);

        $this->assertSame(1, $workflow->getRequiredHumanApprovalCount());
    }

    public function testGetRequiredApprovalCountCanBeZero(): void
    {
        $workflow = new RequestWorkflow('default', [], 0);

        $this->assertSame(0, $workflow->getRequiredHumanApprovalCount());
    }
}
