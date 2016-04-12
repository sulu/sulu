<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Entity;

use Sulu\Bundle\MediaBundle\Entity\Media;

class MediaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Media
     */
    private $media;

    public function setUp()
    {
        $this->media = new Media();
    }

    /**
     * It should throw an exception if the Media is not associated with a collection
     * and getCollection is called.
     *
     * @expectedException \RuntimeException
     */
    public function testGetCollectionNull()
    {
        $this->media->getCollection();
    }
}
