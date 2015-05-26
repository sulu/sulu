<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Rlp\Strategy;

use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\PHPCR\PathCleanup;

class TreeStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RlpMapperInterface
     */
    private $mapper;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    protected function setUp()
    {
        $this->mapper = $this->getMockForAbstractClass(
            'Sulu\Component\Content\Types\Rlp\Mapper\RlpMapper',
            array('test-mapper'),
            'TestMapper'
        );
        $this->mapper->expects($this->any())
            ->method('unique')
            ->will($this->returnCallback(array($this, 'uniqueCallback')));
        $this->mapper->expects($this->any())
            ->method('getUniquePath')
            ->will($this->returnCallback(array($this, 'getUniquePathCallback')));

        $structureManager = $this->getMockForAbstractClass('Sulu\Component\Content\StructureManagerInterface');
        $contentTypeManager = $this->getMockForAbstractClass('Sulu\Component\Content\ContentTypeManagerInterface');
        $nodeHelper = $this->getMock('Sulu\Component\Util\SuluNodeHelper', array(), array(), '', false);

        $this->strategy = new TreeStrategy(
            $this->mapper,
            new PathCleanup(),
            $structureManager,
            $contentTypeManager,
            $nodeHelper
        );
    }

    public function uniqueCallback()
    {
        $args = func_get_args();
        if ($args[0] == '/products/machines' || $args[0] == '/products/machines/drill') {
            return false;
        }

        return true;
    }

    public function getUniquePathCallback()
    {
        $args = func_get_args();
        if ($args[0] == '/products/machines' || $args[0] == '/products/machines/drill') {
            return $args[0] . '-1';
        }

        return $args[0];
    }

    public function testGenerate()
    {
        $result = $this->strategy->generate('machines', '/products', 'default', 'de');
        $this->assertEquals('/products/machines-1', $result);

        $result = $this->strategy->generate('drill', '/products/machines', 'default', 'de');
        $this->assertEquals('/products/machines/drill-1', $result);

        $result = $this->strategy->generate('mä   chines', '/products', 'default', 'de');
        $this->assertEquals('/products/mae-chines', $result);

        $result = $this->strategy->generate('mächines', '/products', 'default', 'de');
        $this->assertEquals('/products/maechines', $result);

        $result = $this->strategy->generate('asdf', null, 'default', 'de');
        $this->assertEquals('/asdf', $result);
    }
}
