<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;

/**
 * This behavior combines the creator and changer with the created and changed dates.
 */
interface AuditableBehavior extends BlameBehavior, TimestampBehavior
{
}
