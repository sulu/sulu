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
 * An instance of this exception signals that no view with given name was found.
 */
class ViewNotFoundException extends \Exception
{
    public function __construct(private string $view)
    {
        parent::__construct(\sprintf('The view with the name "%s" does not exist.', $view));

        $this->view = $view;
    }

    public function getView(): string
    {
        return $this->view;
    }
}
