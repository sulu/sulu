<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\Build;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\CoreBundle\Build\FixturesBuilder;

class FixturesBuilderTest extends TestCase
{
    public function testGetDependencies(): void
    {
        $builder = new FixturesBuilder();

        $this->assertSame(['database'], $builder->getDependencies());
    }

    public function testGetDependenciesWithAdditionalDependencies(): void
    {
        $builder = new FixturesBuilder(['homepage']);

        $this->assertSame(['database', 'homepage'], $builder->getDependencies());
    }

    public function testGetDependenciesRemovesDuplicates(): void
    {
        $builder = new FixturesBuilder(['database', 'homepage', 'homepage']);

        $this->assertSame(['database', 'homepage'], $builder->getDependencies());
    }
}
