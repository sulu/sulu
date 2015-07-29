<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Expression\Exception;

/**
 * Exceptions is throw when an invalid parameter is passed to an expression.
 */
class InvalidArgumentException extends ExpressionException
{
    /**
     * The argument of the expression, which was invalid.
     *
     * @var string
     */
    protected $argument;

    /**
     * The expression type.
     *
     * @var string
     */
    protected $expression;

    /**
     * @param string $expression The type of the expression
     * @param string $argument The argument of the expression, which was invalid
     * @param null $customMessage
     */
    public function __construct($expression, $argument, $customMessage = null)
    {
        $this->entity = $expression;
        $this->argument = $argument;
        $message = 'The "' . $expression . '"-expression requires a valid "' . $argument . '"-argument';
        if ($customMessage != null) {
            $message .= $customMessage;
        }
        parent::__construct($message, 0);
    }

    /**
     * Returns the type of the expression, which was concerned.
     *
     * @return string
     */
    public function getArgument()
    {
        return $this->argument;
    }
}
