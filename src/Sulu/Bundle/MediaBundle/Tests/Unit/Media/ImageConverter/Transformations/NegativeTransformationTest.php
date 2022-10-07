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
use Sulu\Bundle\MediaBundle\Media\ImageConverter\Transformation\NegativeTransformation;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

/**
 * Test the negative transformation.
 */
class NegativeTransformationTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var NegativeTransformation
     */
    protected $transformation;

    public function setUp(): void
    {
        $this->transformation = new NegativeTransformation();

        parent::setUp();
    }

    public function testNegative(): void
    {
        $image = $this->prophesize(ImageInterface::class);
        $effects = $this->prophesize(EffectsInterface::class);
        $effects->negative()->shouldBeCalled();
        $image->effects()->willReturn($effects);

        $returnImage = $this->transformation->execute($image->reveal(), []);

        $this->assertInstanceOf(ImageInterface::class, $returnImage);
    }
}
