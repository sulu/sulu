<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Redirect\Domain\Value;

enum RedirectStatusCodeEnum: int
{
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case GONE = 410;
}
