<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatLoader\Exception;

/**
 * Gets thrown if either 'x' or 'y' coordinate is net set for the scale section.
 */
class MissingScaleDimensionException extends \RuntimeException
{
}
