<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

require_once __DIR__ . '/StructureTestCase.php';

use DateTime;
use Sulu\Component\Content\StructureType;

class PageTest extends StructureTestCase
{
    protected function getStructure()
    {
        $structure = $this->getMockBuilder('Sulu\Component\Content\Structure\Page')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $structure->setType(StructureType::getGhost('en_us'));
        $structure->setChanged(new DateTime('2014-03-18'));
        $structure->setCreated(new DateTime('2014-03-17'));
        $structure->setNodeState(2);
        $structure->setPublished(new DateTime('2014-03-16'));
        $structure->setNavContexts(true);
        $structure->setHasTranslation(true);

        return $structure;
    }
}
