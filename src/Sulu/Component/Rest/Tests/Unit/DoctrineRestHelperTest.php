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

use PHPUnit\Framework\TestCase;
use Sulu\Component\Rest\DoctrineRestHelper;

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
        $this->listRestHelper = $this->getMockBuilder('Sulu\Component\Rest\ListBuilder\ListRestHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->restHelper = new DoctrineRestHelper($this->listRestHelper);
    }

    public function testProcessSubEntities(): void
    {
        $entities = $this->getMockBuilder('Doctrine\Common\Collections\Collection')
            ->getMockForAbstractClass();

        $entities->expects($this->once())->method('count')->willReturn(2);
        $entities->expects($this->once())->method('getValues')->willReturn([null, null]);
        $entities->expects($this->once())->method('clear');
        $entities->expects($this->once())->method('getIterator')->willReturn(new \ArrayIterator([null, null]));
        $entities->expects($this->exactly(2))->method('add');

        $this->restHelper->processSubEntities($entities, [], function() {
        });
    }
}
