<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Csv;

use FOS\RestBundle\View\View;

/**
 * The view is not supported to create a csv-response.
 */
class ObjectNotSupportedException extends \Exception
{
    public function __construct(private View $view)
    {
        parent::__construct('The view is not supported to create a csv-response.');
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }
}
