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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Application\BlockIdGenerator;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Application\BlockIdGenerator\BlockIdGenerator;

class BlockIdGeneratorTest extends TestCase
{
    private BlockIdGenerator $blockIdGenerator;

    protected function setUp(): void
    {
        $this->blockIdGenerator = new BlockIdGenerator();
    }

    public function testGenerateId(): void
    {
        $id = $this->blockIdGenerator->generateId();

        $this->assertNotEmpty($id);
        $this->assertSame(8, \strlen($id));
    }

    public function testGenerateIdFormat(): void
    {
        $id = $this->blockIdGenerator->generateId();

        // xxh32 produces 8-character hexadecimal strings
        $this->assertSame(8, \strlen($id));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}$/', $id);
    }

    public function testConsecutiveCallsProduceDifferentIds(): void
    {
        $id1 = $this->blockIdGenerator->generateId();

        // Small delay to ensure different timestamp
        \usleep(10);

        $id2 = $this->blockIdGenerator->generateId();

        $this->assertNotSame($id1, $id2);
    }
}
