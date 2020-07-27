<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\System;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\System\SystemStore;

class SystemStoreTest extends TestCase
{
    /**
     * @var SystemStore
     */
    private $systemStore;

    public function setUp(): void
    {
        $this->systemStore = new SystemStore();
    }

    public function testSetSystem()
    {
        $this->systemStore->setSystem('Sulu');
        $this->assertEquals('Sulu', $this->systemStore->getSystem());
        $this->systemStore->setSystem('Sulu Test');
        $this->assertEquals('Sulu Test', $this->systemStore->getSystem());
    }
}
