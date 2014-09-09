<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\ContentBundle\Content\Types\InternalLinks;
use Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface;

class InternalLinksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NodeRepositoryInterface
     */
    private $nodeRepository;

    /**
     * @var InternalLinks
     */
    private $internalLinks;

    protected function setUp()
    {
        $this->nodeRepository = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Repository\NodeRepository',
            array(),
            '',
            false
        );
        $this->internalLinks = new InternalLinks($this->nodeRepository, 'asdf');
    }

    public function testWriteWithNoneExistingUUID()
    {
        $subNode1 = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true
        );
        $subNode1->expects($this->any())->method('getIdentifier')->will($this->returnValue('123-123-123'));
        $subNode2 = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true
        );
        $subNode2->expects($this->any())->method('getIdentifier')->will($this->returnValue('123-456-789'));

        $node = $this->getMockForAbstractClass(
            'Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types\NodeInterface',
            array(),
            '',
            true,
            true,
            true
        );

        $session = $this->getMockForAbstractClass(
            'PHPCR\SessionInterface',
            array(),
            '',
            true,
            true,
            true
        );

        $node->expects($this->any())->method('getSession')->will($this->returnValue($session));
        $session->expects($this->any())->method('getNodesByIdentifier')->will(
            $this->returnValue(array($subNode1, $subNode2))
        );

        $property = $this->getMockForAbstractClass(
            'Sulu\Component\Content\PropertyInterface',
            array(),
            '',
            true,
            true,
            true
        );

        $property->expects($this->any())->method('getName')->will($this->returnValue('property'));

        $property->expects($this->any())->method('getValue')->will(
            $this->returnValue(
                array(
                    'ids' => array(
                        '123-123-123',
                        '123-456-789',
                        'not existing'
                    )
                )
            )
        );

        $node->expects($this->once())->method('setProperty')->with(
            'property',
            json_encode(
                array(
                    'ids' => array(
                        '123-123-123',
                        '123-456-789'
                    )
                )
            )
        );

        $this->internalLinks->write($node, $property, 1, 'test', 'de', null);
    }
}
