<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use FOS\RestBundle\Util\ExceptionWrapper;
use FOS\RestBundle\View\ExceptionWrapperHandlerInterface;

/**
 * Our handler for thrown exceptions in REST controllers.
 */
class ExceptionWrapperHandler implements ExceptionWrapperHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function wrap($data)
    {
        $data['status_code'] = $data['exception']->getCode();
        $data['errors'] = [$data['exception']];

        return new ExceptionWrapper($data);
    }
}
