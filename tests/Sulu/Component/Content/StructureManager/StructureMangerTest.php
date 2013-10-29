<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Jackalope\Session;
use PHPCR\Util\NodeHelper;
use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Mapper\PhpcrContentMapper;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Types\ResourceLocator;
use Sulu\Component\Content\Types\TextArea;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryService;

class StructureMangerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    public function testGetStructure()
    {
        /** @var StructureInterface $structure */
        $structure = $this->structureManager->getStructure('overview');
    }
}
