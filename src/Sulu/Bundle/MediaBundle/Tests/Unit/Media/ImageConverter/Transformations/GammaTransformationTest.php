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
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\GammaTransformation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Test the gamma transformation.
 */
class GammaTransformationTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var GammaTransformation
     */
    protected $transformation;

    public function setUp(): void
    {
        $this->transformation = new GammaTransformation();

        parent::setUp();
    }

    public function testGamma(): void
    {
        $image = $this->prophesize(ImageInterface::class);
        $effects = $this->prophesize(EffectsInterface::class);
        $effects->gamma(0.75)->shouldBeCalled();
        $image->effects()->willReturn($effects);

        $returnImage = $this->transformation->execute(
            $image->reveal(),
            [
                'correction' => '0.75',
            ]
        );

        $this->assertInstanceOf(ImageInterface::class, $returnImage);
    }
}
