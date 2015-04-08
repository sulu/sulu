<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit;

use Sulu\Component\DocumentManager\DocumentRegistry;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\DocumentAccessor;

class DocumentAccessorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->object = new TestAccessObject();
        $this->accessor = new DocumentAccessor($this->object);
    }

    /**
     * It should be able to set private properties
     */
    public function testAccessObject()
    {
        $this->accessor->set('privateProperty', 'Hai');
        $this->assertEquals('Hai', $this->object->getPrivateProperty());
    }

    /**
     * It should throw an exception if the property does not exist
     *
     * @expectedException Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function testAccessObjectNotExist()
    {
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
