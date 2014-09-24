<?php
/*
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Unit\Repository;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Bundle\ContentBundle\Repository\FilterNodesQueryBuilder;

class FilterNodesQueryBuilderTest extends ProphecyTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->sessionManager = $this->prophesize('Sulu\Component\PHPCR\SessionManager\SessionManagerInterface');
        $this->webspaceManager = $this->prophesize('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
    }

    public function provideBuild()
    {
        return array(
            array(
                array(),
                'SELECT * FROM [nt:base] AS c WHERE c.[jcr:mixinTypes] = "sulu:content" AND c.[i18n:de-state] = 2 ORDER BY c.[sulu:order] DESC',
            ),
            array(
                array(
                    'sortBy' => array('foobar'),
                ),
                'SELECT * FROM [nt:base] AS c WHERE c.[jcr:mixinTypes] = "sulu:content" AND c.[i18n:de-state] = 2 ORDER BY lower(c.[i18n:de-foobar]) DESC',
            ),
            array(
                array(
                    'sortBy' => array('foobar', 'created'),
                ),
                'SELECT * FROM [nt:base] AS c WHERE c.[jcr:mixinTypes] = "sulu:content" AND c.[i18n:de-state] = 2 ORDER BY lower(c.[i18n:de-foobar]), c.[i18n:de-created] DESC',
            ),
        );
    }

    /**
     * @dataProvider provideBuild
     */
    public function testBuild($config, $expected)
    {
        $qb = $this->getQueryBuilder($config);
        $res = $qb->build('de');

        $this->assertEquals($expected, $res);
    }

    private function getQueryBuilder($config)
    {
        return new FilterNodesQueryBuilder(
            $config,
            $this->sessionManager->reveal(),
            $this->webspaceManager->reveal()
        );
    }
}
