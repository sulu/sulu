<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Domain\Value;

enum RequestAttributeEnum: string
{
    case SITE = 'site';

    case SLUG = 'slug';
}
