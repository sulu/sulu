<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\InternalLink;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Bundle\ContentBundle\Content\InternalLinksContainer;
use Sulu\Component\Content\Query\ContentQueryBuilder;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Symfony\Component\HttpKernel\Log\NullLogger;

class InternalLinksContainerTest extends ProphecyTestCase
{
    /**
     * @var InternalLinksContainer
     */
    private $container;

    /**
     * @var ContentQueryExecutor
     */
    private $executor;

    /**
     * @var ContentQueryBuilder
     */
    private $builder;

    public function testOrder()
    {
        $this->executor->execute('default', array('en'), $this->builder)->willReturn(
            array(array('uuid' => 1), array('uuid' => 2), array('uuid' => 3))
        );

        $this->container = new InternalLinksContainer(
            array(2, 3, 1),
            $this->executor->reveal(),
            $this->builder->reveal(),
            array(),
            new NullLogger(),
            'default',
            'en'
        );

        $result = $this->container->getData();
        $this->assertEquals(array(array('uuid' => 2), array('uuid' => 3), array('uuid' => 1)), $result);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->executor = $this->prophesize('Sulu\Component\Content\Query\ContentQueryExecutor');
        $this->builder = $this->prophesize('Sulu\Component\Content\Query\ContentQueryBuilder');
    }
}
