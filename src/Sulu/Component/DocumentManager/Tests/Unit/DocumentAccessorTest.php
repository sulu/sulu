<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class DocumentAccessorTest extends TestCase
{
    public function setUp(): void
    {
        $this->object = new TestAccessObject();
        $this->accessor = new DocumentAccessor($this->object);
    }

    /**
     * It should be able to set private properties.
     */
    public function testAccessObject(): void
    {
        $this->accessor->set('privateProperty', 'Hai');
        $this->assertEquals('Hai', $this->object->getPrivateProperty());
    }

    /**
     * It should throw an exception if the property does not exist.
     */
    public function testAccessObjectNotExist(): void
    {
        $this->expectException(DocumentManagerException::class);
        $this->accessor->set('asdf', 'asd');
    }
}

class TestAccessObject
{
    private $privateProperty;

    public function getPrivateProperty()
    {
        return $this->privateProperty;
    }
}
