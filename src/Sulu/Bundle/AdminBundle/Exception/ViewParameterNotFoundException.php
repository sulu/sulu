<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Exception;

/**
 * An instance of this exception signals that a view could not be generated because
 * a parameter required by its path was not given.
 */
class ViewParameterNotFoundException extends \Exception
{
    public function __construct(private string $parameter, private string $view)
    {
        parent::__construct(
            \sprintf(
                'The parameter "%s" is required to generate the url for the view "%s" but was not given.',
                $parameter,
                $view
            )
        );
    }

    public function getParameter(): string
    {
        return $this->parameter;
    }

    public function getView(): string
    {
        return $this->view;
    }
}
