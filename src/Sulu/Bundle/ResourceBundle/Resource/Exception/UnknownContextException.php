<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Exception;

/**
 * Exception which is thrown when a context is unknown.
 */
class UnknownContextException extends FilterException
{
    /**
     * @var string
     */
    private $context;

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    public function __construct($context)
    {
        $this->context = $context;
        parent::__construct('The context ' . $context . ' is unknown!', 0);
    }
}
