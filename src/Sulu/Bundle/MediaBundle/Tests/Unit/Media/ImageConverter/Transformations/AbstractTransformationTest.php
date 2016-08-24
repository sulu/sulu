<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Transformations;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\TransformationInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Class AbstractScaleTest.
 */
abstract class AbstractTransformationTest extends SuluTestCase
{
    /**
     * @var TransformationInterface
     */
    protected $transformation;

    /**
     * @var string
     */
    protected $transformationServiceName;

    /**
     * @var int
     */
    protected $imageWidth = 700;

    /**
     * @var int
     */
    protected $imageHeight = 500;

    public function setUp()
    {
        parent::setUp();
        $this->transformation = $this->getContainer()->get($this->transformationServiceName);
    }

    /**
     * @return array
     */
    abstract protected function getDataList();

    public function testTransformation()
    {
        foreach ($this->getDataList() as $data) {
            $imageWidth = $this->imageWidth;
            if (isset($data['imageWidth'])) {
                $imageWidth = $data['imageWidth'];
            }

            $imageHeight = $this->imageHeight;
            if (isset($data['imageHeight'])) {
                $imageHeight = $data['imageHeight'];
            }

            $imagine = new Imagine();
            $imageBox = new Box($imageWidth, $imageHeight);
            $image = $imagine->create($imageBox);

            $image = $this->transformation->execute($image, $data['options']);

            $this->assertEquals($data['width'], $image->getSize()->getWidth());
            $this->assertEquals($data['height'], $image->getSize()->getHeight());
        }
    }
}
