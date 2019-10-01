<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use FOS\RestBundle\Controller\FOSRestController;

/**
 * Abstract Controller for extracting some required rest functionality.
 *
 * @deprecated since Sulu 2.0, use {@see AbstractRestController} instead
 */
abstract class RestController extends FOSRestController
{
    use RestControllerTrait;
}
