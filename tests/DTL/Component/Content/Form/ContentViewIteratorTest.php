<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Form;

use DTL\Component\Content\Form\ContentView;
use DTL\Component\Content\Form\ContentViewIterator;
use DTL\Component\Content\Form\ContentViewResolver;
use Prophecy\PhpUnit\ProphecyTestCase;
use DTL\Bundle\ContentBundle\Document\StructureDocument;

class ContentViewIteratorTest extends ProphecyTestCase
{
    /**
     * @var ContentViewIterator
     */
    private $iterator;

    /**
     * @var ContentViewResolver
     */
    private $resolver;

    /**
     * @var ContentView
     */
    private $contentView1;

    /**
     * @var ContentView
     */
    private $contentView2;

    /**
     * @var StructureDocument
     */
    private $document1;

    /**
     * @var StructureDocument
     */
    private $document2;

    public function setUp()
    {
        parent::setUp();

        $this->resolver = $this->prophesize('DTL\Component\Content\Form\ContentViewResolver');
        $this->document1 = $this->prophesize('DTL\Bundle\ContentBundle\Document\StructureDocument');
        $this->document2 = $this->prophesize('DTL\Bundle\ContentBundle\Document\StructureDocument');
        $this->contentView1 = $this->prophesize('DTL\Component\Content\Form\ContentView');
        $this->contentView1->getValue()->willReturn('one');
        $this->contentView2 = $this->prophesize('DTL\Component\Content\Form\ContentView');
        $this->contentView2->getValue()->willReturn('two');

        $this->iterator = new ContentViewIterator($this->resolver->reveal(), array(
            $this->document1->reveal(),
            $this->document2->reveal(),
        ));

        $this->resolver->resolve($this->document1->reveal())->willReturn($this->contentView1->reveal());
        $this->resolver->resolve($this->document2->reveal())->willReturn($this->contentView2->reveal());
    }

    public function testIterator()
    {
        $expectedStack = array(
            $this->contentView1->reveal(),
            $this->contentView2->reveal(),
        );

        $this->assertCount(2, $this->iterator);

        foreach ($this->iterator as $view) {
            $expectedView = array_shift($expectedStack);
            $this->assertEquals($expectedView, $view);
        }
    }
}
