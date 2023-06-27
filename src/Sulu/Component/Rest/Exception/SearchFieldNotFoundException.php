<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

class SearchFieldNotFoundException extends RestException
{
    public function __construct(private string $field)
    {
        parent::__construct(\sprintf('The "%s" field does not exist, but was requested as a search field', $field));
    }
}
