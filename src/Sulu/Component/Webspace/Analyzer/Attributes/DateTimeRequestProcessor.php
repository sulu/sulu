<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Symfony\Component\HttpFoundation\Request;

class DateTimeRequestProcessor implements RequestProcessorInterface
{
    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        return new RequestAttributes(['dateTime' => new \DateTime()]);
    }

    public function validate(RequestAttributes $attributes)
    {
        return true;
    }
}
