<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\ContentBundle\Preview;

/**
 * Represents a not started preview
 */
class PreviewNotStartedException extends \Exception
{
    function __construct()
    {
        parent::__construct(sprintf('Preview not started.'), 3001);
    }
}
