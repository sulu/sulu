<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider;

interface RestoreConfigurationProviderInterface
{
    public function getConfiguration(): RestoreConfiguration;

    public static function getResourceKey(): string;
}
