<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\DoctrineRestHelper;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;

class DoctrineRestHelperTest extends TestCase
{
    /**
     * @var DoctrineRestHelper
     */
    private $restHelper;

    /**
     * @var \PHPUnit\Framework\MockObject_MockObject
     */
    private $listRestHelper;

    public function setUp(): void
    {
        $this->listRestHelper = $this->getMockBuilder(ListRestHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->restHelper = new DoctrineRestHelper($this->listRestHelper);
    }

    public function testProcessSubEntities(): void
    {
        /** @var ArrayCollection<array-key, mixed> $entities */
        $entities = new ArrayCollection(['test' => true, 'hello' => null, 'foo' => false]);

        $this->restHelper->processSubEntities($entities, [], function() {});

        $this->assertSame($entities->toArray(), [true, null, false]);
    }
}
