<?php

namespace Sulu\Component\Content\Mapper\LocalizationFinder;

/**
 * Chain localization finder.
 */
class ChainFinderTest extends \PHPUnit_Framework_TestCase
{
    private $finder1;
    private $finder2;
    private $chain;
    private $node;

    public function setUp()
    {
        parent::setUp();
        $this->finder1 = $this->prophesize('Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface');
        $this->finder2 = $this->prophesize('Sulu\Component\Content\Mapper\LocalizationFinder\LocalizationFinderInterface');
        $this->node = $this->prophesize('PHPCR\NodeInterface');

        $this->chain = new ChainFinder();
        $this->chain->addFinder($this->finder1->reveal());
        $this->chain->addFinder($this->finder2->reveal());
    }

    public function testForFind()
    {
        $this->finder1->supports($this->node->reveal(), 'de', 'foobar')->willReturn(false);
        $this->finder2->supports($this->node->reveal(), 'de', 'foobar')->willReturn(true);
        $this->finder2->getAvailableLocalization($this->node->reveal(), 'de', 'foobar')->willReturn('fr');

        $res = $this->chain->getAvailableLocalization($this->node->reveal(), 'de', 'foobar');

        $this->assertEquals('fr', $res);
    }

    public function testForFindNone()
    {
        $this->finder1->supports($this->node->reveal(), 'de', 'foobar')->willReturn(false);
        $this->finder2->supports($this->node->reveal(), 'de', 'foobar')->willReturn(false);

        $res = $this->chain->getAvailableLocalization($this->node->reveal(), 'de', 'foobar');

        $this->assertEquals(null, $res);
    }
}
