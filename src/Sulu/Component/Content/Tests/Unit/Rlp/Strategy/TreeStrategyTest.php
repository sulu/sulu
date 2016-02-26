<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Rlp\Strategy;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\TreeStrategy;
use Sulu\Component\PHPCR\PathCleanup;

class TreeStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $replacers = [
        'default' => [
            ' ' => '-',
            '+' => '-',
            '.' => '-',
        ],
        'de' => [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'Ä' => 'ae',
            'Ö' => 'oe',
            'Ü' => 'ue',
            'ß' => 'ss',
            '&' => 'und',
        ],
        'en' => [
            '&' => 'and',
        ],
    ];

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
            ['test-mapper'],
            'TestMapper'
        );
        $this->mapper->expects($this->any())
            ->method('unique')
            ->will($this->returnCallback([$this, 'uniqueCallback']));
        $this->mapper->expects($this->any())
            ->method('getUniquePath')
            ->will($this->returnCallback([$this, 'getUniquePathCallback']));

        $structureManager = $this->getMockForAbstractClass('Sulu\Component\Content\Compat\StructureManagerInterface');
        $contentTypeManager = $this->getMockForAbstractClass('Sulu\Component\Content\ContentTypeManagerInterface');
        $nodeHelper = $this->getMock('Sulu\Component\Util\SuluNodeHelper', [], [], '', false);
        $documentInspector = $this->getMockBuilder(DocumentInspector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->strategy = new TreeStrategy(
            $this->mapper,
            new PathCleanup($this->replacers),
            $structureManager,
            $contentTypeManager,
            $nodeHelper,
            $documentInspector
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
