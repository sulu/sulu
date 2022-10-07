<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Unit\Command\DataFixtures;

use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureGroupInterface;
use Sulu\Bundle\DocumentManagerBundle\DataFixtures\DocumentFixtureInterface;
use Sulu\Component\DocumentManager\DocumentManager;

class GroupFooFixture implements DocumentFixtureInterface, DocumentFixtureGroupInterface
{
    public function load(DocumentManager $documentManager): void
    {
        return;
    }

    public function getOrder()
    {
        return 0;
    }

    public function getGroups(): array
    {
        return ['Group 1'];
    }
}
