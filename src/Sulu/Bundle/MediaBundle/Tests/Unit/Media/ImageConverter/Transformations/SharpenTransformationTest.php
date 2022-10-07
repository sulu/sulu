<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ImageConverter\Transformations;

use Imagine\Effects\EffectsInterface;
use Imagine\Image\ImageInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\SharpenTransformation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Test the sharpen transformation.
 */
class SharpenTransformationTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var SharpenTransformation
     */
    protected $transformation;

    public function setUp(): void
    {
        $this->transformation = new SharpenTransformation();

        parent::setUp();
    }

    public function testSharpen(): void
    {
        $image = $this->prophesize(ImageInterface::class);
        $effects = $this->prophesize(EffectsInterface::class);
        $effects->sharpen()->shouldBeCalled();
        $image->effects()->willReturn($effects);

        $returnImage = $this->transformation->execute($image->reveal(), []);

        $this->assertInstanceOf(ImageInterface::class, $returnImage);
    }
}
