<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\FrontView;

use DTL\Component\Content\FrontView\ContentView;
use DTL\Component\Content\FrontView\ContentViewIterator;
use DTL\Component\Content\FrontView\ContentViewBuilder;
use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Bundle\ContentBundle\Document\ContentDocument;

class FrontViewIteratorTest extends ProphecyTestCase
{
    /**
     * @var FrontViewIterator
     */
    private $iterator;

    /**
     * @var FrontViewBuilder
     */
    private $builder;

    /**
     * @var FrontView
     */
    private $frontView1;

    /**
     * @var FrontView
     */
    private $frontView2;

    /**
     * @var ContentDocument
     */
    private $document1;

    /**
     * @var ContentDocument
     */
    private $document2;

    public function setUp()
    {
        parent::setUp();

        $this->builder = $this->prophesize('DTL\Component\Content\FrontView\FrontViewBuilder');
        $this->document1 = $this->prophesize('DTL\Component\Content\Document\DocumentInterface');
        $this->document2 = $this->prophesize('DTL\Component\Content\Document\DocumentInterface');
        $this->frontView1 = $this->prophesize('DTL\Component\Content\FrontView\FrontView');
        $this->frontView1->getValue()->willReturn('one');
        $this->frontView2 = $this->prophesize('DTL\Component\Content\FrontView\FrontView');
        $this->frontView2->getValue()->willReturn('two');

        $this->iterator = new FrontViewIterator($this->builder->reveal(), array(
            $this->document1->reveal(),
            $this->document2->reveal(),
        ));

        $this->builder->buildFor($this->document1->reveal())->willReturn($this->frontView1->reveal());
        $this->builder->buildFor($this->document2->reveal())->willReturn($this->frontView2->reveal());
    }

    public function testIterator()
    {
        $expectedStack = array(
            $this->frontView1->reveal(),
            $this->frontView2->reveal(),
        );

        $this->assertCount(2, $this->iterator);

        foreach ($this->iterator as $view) {
            $expectedView = array_shift($expectedStack);
            $this->assertEquals($expectedView, $view);
        }
    }
}
