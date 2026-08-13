<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

/**
 * Builder for loading the fixtures.
 */
class FixturesBuilder extends SuluBuilder
{
    /**
     * @var string[]
     */
    private array $additionalDependencies = [];

    /**
     * Fixtures often require data which is created by builders of optional bundles. As the core bundle can not
     * depend on those builders, they can register themselves as additional dependency instead.
     *
     * @param string[] $additionalDependencies
     */
    public function __construct(array $additionalDependencies = [])
    {
        $this->additionalDependencies = $additionalDependencies;
    }

    public function getName()
    {
        return 'fixtures';
    }

    public function getDependencies()
    {
        return \array_values(\array_unique(\array_merge(['database'], $this->additionalDependencies)));
    }

    public function build()
    {
        $this->execCommand('Loading ORM fixtures', 'doctrine:fixtures:load', ['--no-interaction' => true, '--append' => true]);
    }
}
