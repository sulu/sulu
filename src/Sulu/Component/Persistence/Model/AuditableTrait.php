<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Model;

/**
 * Trait with basic implementation of AuditableInterface.
 */
trait AuditableTrait
{
    use UserBlameTrait;
    use TimestampableTrait;
}
